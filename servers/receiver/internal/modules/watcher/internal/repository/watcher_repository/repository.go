package watcher_repository

import (
	"context"
	"encoding/json"
	"log/slog"
	"slogger_receiver/internal/config"
	"slogger_receiver/pkg/foundation/errs"
	"sync"

	"slogger_receiver/internal/platform/mysql"
)

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
	Statuses   []string `json:"statuses"`
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
		instance = &Repository{
			conn:  mysql.Get(),
			table: config.Get().Mysql().TableWatchers(),
		}
	})

	return instance
}

type Repository struct {
	conn  *mysql.Connection
	table string
}

// FindEnabled returns the watchers this service has anything to do with: enabled, not
// deleted, and carrying a filter. The ones without a filter watch the buffers, which the
// panel reads for itself.
//
// Deleted is soft — the panel keeps the row so that the incidents a watcher left behind
// still have a name to show against — so it has to be asked for here rather than assumed
// from the row being gone.
//
// A row whose json will not parse is skipped and logged rather than failing the whole
// read: one broken watcher must not stop the others from being collected for.
func (r *Repository) FindEnabled(ctx context.Context) ([]Watcher, int, error) {
	db, err := r.conn.DB()

	if err != nil {
		return nil, 0, errs.Err(err)
	}

	var rows []row

	err = db.SelectContext(
		ctx,
		&rows,
		"SELECT id, trace_match FROM "+r.table+" WHERE enabled = 1 AND deleted_at IS NULL AND trace_match IS NOT NULL",
	)

	if err != nil {
		return nil, 0, errs.Err(err)
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

	return watchers, len(rows), nil
}

func (r *Repository) Close() error {
	return r.conn.Close()
}
