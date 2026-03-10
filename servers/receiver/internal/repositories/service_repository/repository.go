package service_repository

import (
	"context"
	"os"
	"slogger_receiver/pkg/foundation/errs"
	"sync"
	"time"

	_ "github.com/go-sql-driver/mysql"
	"github.com/jmoiron/sqlx"
)

var instance *Repository
var once sync.Once

type cacheEntry struct {
	serviceId int
	expiresAt time.Time
}

type Repository struct {
	db *sqlx.DB

	host     string
	port     string
	database string
	username string
	password string
	table    string

	mutex      sync.Mutex
	cache      map[string]cacheEntry
	cacheMutex sync.RWMutex
}

func Get() *Repository {
	once.Do(func() {
		instance = &Repository{
			cache: make(map[string]cacheEntry),
		}

		instance.host = os.Getenv("MYSQL_HOST")
		instance.port = os.Getenv("MYSQL_PORT")
		instance.database = os.Getenv("MYSQL_DATABASE")
		instance.username = os.Getenv("MYSQL_USERNAME")
		instance.password = os.Getenv("MYSQL_PASSWORD")
		instance.table = os.Getenv("MYSQL_TABLE_SERVICES")
	})

	return instance
}

func (s *Repository) FindIdByApiToken(ctx context.Context, apiToken string) (int, error) {
	s.cacheMutex.RLock()

	if entry, exists := s.cache[apiToken]; exists {
		if time.Now().Before(entry.expiresAt) {
			s.cacheMutex.RUnlock()

			return entry.serviceId, nil
		}
	}

	s.cacheMutex.RUnlock()

	err := s.connect()

	if err != nil {
		return 0, errs.Err(err)
	}

	var ids []int

	err = s.db.SelectContext(
		ctx,
		&ids,
		"SELECT id FROM "+s.table+" WHERE api_token = ? LIMIT 1",
		apiToken,
	)

	if err != nil {
		return 0, err
	}

	if len(ids) == 0 {
		return 0, nil
	}

	serviceId := ids[0]

	// Update cache
	s.cacheMutex.Lock()
	defer s.cacheMutex.Unlock()

	s.cache[apiToken] = cacheEntry{
		serviceId: serviceId,
		expiresAt: time.Now().Add(1 * time.Minute),
	}

	return serviceId, nil
}

func (s *Repository) connect() error {
	s.mutex.Lock()
	defer s.mutex.Unlock()

	if s.db != nil {
		return nil
	}

	db, err := sqlx.Connect(
		"mysql",
		s.username+":"+s.password+"@tcp("+s.host+":"+s.port+")/"+s.database,
	)

	if err != nil {
		return errs.Err(err)
	}

	s.db = db

	return nil
}

func (s *Repository) Close() error {
	if s.db != nil {
		err := s.db.Close()

		if err != nil {
			return errs.Err(err)
		}

		s.db = nil
	}

	return nil
}
