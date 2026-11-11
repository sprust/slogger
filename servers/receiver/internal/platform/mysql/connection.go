package mysql

import (
	"os"
	"slogger_receiver/pkg/foundation/errs"
	"sync"

	_ "github.com/go-sql-driver/mysql"
	"github.com/jmoiron/sqlx"
)

var instance *Connection
var once sync.Once

type Connection struct {
	host     string
	port     string
	database string
	username string
	password string

	db    *sqlx.DB
	mutex sync.Mutex
}

func Get() *Connection {
	once.Do(func() {
		instance = &Connection{
			host:     os.Getenv("MYSQL_HOST"),
			port:     os.Getenv("MYSQL_PORT"),
			database: os.Getenv("MYSQL_DATABASE"),
			username: os.Getenv("MYSQL_USERNAME"),
			password: os.Getenv("MYSQL_PASSWORD"),
		}
	})

	return instance
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
