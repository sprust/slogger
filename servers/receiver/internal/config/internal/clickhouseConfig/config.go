package clickhouseConfig

import (
	"os"
	"sync"
)

var once sync.Once
var instance Config

type Config interface {
	Url() string
	Database() string
	Username() string
	Password() string
}

type mysql struct {
	url      string
	database string
	username string
	password string
}

func Get() Config {
	once.Do(func() {
		instance = &mysql{
			url:      os.Getenv("CLICKHOUSE_URL"),
			database: os.Getenv("CLICKHOUSE_DATABASE"),
			username: os.Getenv("CLICKHOUSE_USERNAME"),
			password: os.Getenv("CLICKHOUSE_PASSWORD"),
		}
	})

	return instance
}

func (c *mysql) Url() string {
	return c.url
}

func (c *mysql) Database() string {
	return c.database
}

func (c *mysql) Username() string {
	return c.username
}

func (c *mysql) Password() string {
	return c.password
}
