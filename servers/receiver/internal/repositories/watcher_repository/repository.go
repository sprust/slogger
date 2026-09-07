package watcher_repository

import (
	"context"
	"encoding/json"
	"log/slog"
	"os"
	"slogger_receiver/pkg/foundation/errs"
	"sync"

	_ "github.com/go-sql-driver/mysql"
	"github.com/jmoiron/sqlx"
)

// defaultTable is used when the environment does not name one, so that an installation
// that has not updated its .env still collects rather than silently reading nothing.
const defaultTable = "watchers"

// maxOpenConns bounds this pool deliberately: the whole of its traffic is one query every
// thirty seconds, and it shares a MySQL server with the service lookups on the hot path.
const maxOpenConns = 2

// Match is the entire contract between the panel and this service: the filter of one
// watcher, as the `trace_match` column carries it.
//
// Version is read rather than assumed. A panel newer than this binary can write a shape
// this code would misread, and a filter misread is a watcher quietly counting the wrong
// traces — which nobody would notice, because the number it produces looks like a number.
type Match struct {
	Version    int      `json:"v"`
	ServiceIds []int    `json:"service_ids"`
	Types      []string `json:"types"`
	Tags       []string `json:"tags"`
}

type Watcher struct {
	Id    int
	Match Match
}

type row struct {
	Id         int    `db:"id"`
	TraceMatch []byte `db:"trace_match"`
}

var instance *Repository
var once sync.Once

func Get() *Repository {
	once.Do(func() {
		instance = &Repository{}

		instance.host = os.Getenv("MYSQL_HOST")
		instance.port = os.Getenv("MYSQL_PORT")
		instance.database = os.Getenv("MYSQL_DATABASE")
		instance.username = os.Getenv("MYSQL_USERNAME")
		instance.password = os.Getenv("MYSQL_PASSWORD")
		instance.table = os.Getenv("MYSQL_TABLE_WATCHERS")

		if instance.table == "" {
			instance.table = defaultTable
		}
	})

	return instance
}

type Repository struct {
	db *sqlx.DB

	host     string
	port     string
	database string
	username string
	password string
	table    string

	mutex sync.Mutex
}

// FindEnabled returns the watchers this service has anything to do with: enabled, and
// carrying a filter. The ones without a filter watch the buffers, which the panel reads
// for itself.
//
// A row whose json will not parse is skipped and logged rather than failing the whole
// read: one broken watcher must not stop the others from being collected for.
func (r *Repository) FindEnabled(ctx context.Context) ([]Watcher, error) {
	if err := r.connect(); err != nil {
		return nil, errs.Err(err)
	}

	var rows []row

	err := r.db.SelectContext(
		ctx,
		&rows,
		"SELECT id, trace_match FROM "+r.table+" WHERE enabled = 1 AND trace_match IS NOT NULL",
	)

	if err != nil {
		return nil, errs.Err(err)
	}

	watchers := make([]Watcher, 0, len(rows))

	for _, item := range rows {
		var match Match

		if err := json.Unmarshal(item.TraceMatch, &match); err != nil {
			slog.Error(errs.Err(err).Error(), "watcher", item.Id)

			continue
		}

		watchers = append(watchers, Watcher{Id: item.Id, Match: match})
	}

	return watchers, nil
}

func (r *Repository) connect() error {
	r.mutex.Lock()
	defer r.mutex.Unlock()

	if r.db != nil {
		return nil
	}

	db, err := sqlx.Connect(
		"mysql",
		r.username+":"+r.password+"@tcp("+r.host+":"+r.port+")/"+r.database,
	)

	if err != nil {
		return errs.Err(err)
	}

	db.SetMaxOpenConns(maxOpenConns)

	r.db = db

	return nil
}

func (r *Repository) Close() error {
	r.mutex.Lock()
	defer r.mutex.Unlock()

	if r.db == nil {
		return nil
	}

	err := r.db.Close()

	r.db = nil

	if err != nil {
		return errs.Err(err)
	}

	return nil
}
