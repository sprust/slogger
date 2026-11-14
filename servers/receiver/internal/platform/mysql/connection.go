package mysql

import (
	"slogger_receiver/internal/config"
	"slogger_receiver/pkg/foundation/errs"
	"sync"

	_ "github.com/go-sql-driver/mysql"
	"github.com/jmoiron/sqlx"
)

var instance *Connection
var once sync.Once

type Connection struct {
	host           string
	port           string
	database       string
	username       string
	password       string
	maxConnections int

	db    *sqlx.DB
	mutex sync.Mutex
}

func Get() *Connection {
	once.Do(func() {
		conf := config.Get().Mysql()

		instance = New(
			conf.Host(),
			conf.Port(),
			conf.Database(),
			conf.Username(),
			conf.Password(),
			conf.MaxConnections(),
		)
	})

	return instance
}

func New(
	host string,
	port string,
	database string,
	username string,
	password string,
	maxOpenConns int,
) *Connection {
	return &Connection{
		host:           host,
		port:           port,
		database:       database,
		username:       username,
		password:       password,
		maxConnections: maxOpenConns,
	}
}

func (c *Connection) DB() (*sqlx.DB, error) {
	c.mutex.Lock()
	defer c.mutex.Unlock()

	if c.db != nil {
		return c.db, nil
	}

	db, err := sqlx.Connect(
		"mysql",
		c.username+":"+c.password+"@tcp("+c.host+":"+c.port+")/"+c.database,
	)

	if err != nil {
		return nil, errs.Err(err)
	}

	db.SetMaxOpenConns(c.maxConnections)

	c.db = db

	return c.db, nil
}

func (c *Connection) Close() error {
	c.mutex.Lock()
	defer c.mutex.Unlock()

	if c.db != nil {
		err := c.db.Close()

		if err != nil {
			return errs.Err(err)
		}

		c.db = nil
	}

	return nil
}
