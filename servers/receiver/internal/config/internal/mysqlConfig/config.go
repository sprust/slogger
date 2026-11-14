package mysqlConfig

import (
	"os"
	"sync"
)

var once sync.Once
var instance Config

type Config interface {
	Host() string
	Port() string
	Database() string
	Username() string
	Password() string
	MaxConnections() int
	TableServices() string
	TableWatchers() string
}

type mysql struct {
	host           string
	port           string
	database       string
	username       string
	password       string
	maxConnections int
	tableServices  string
	tableWatchers  string
}

func Get() Config {
	once.Do(func() {
		instance = &mysql{
			host:           os.Getenv("MYSQL_HOST"),
			port:           os.Getenv("MYSQL_PORT"),
			database:       os.Getenv("MYSQL_DATABASE"),
			username:       os.Getenv("MYSQL_USERNAME"),
			password:       os.Getenv("MYSQL_PASSWORD"),
			maxConnections: 0,
			tableServices:  os.Getenv("MYSQL_TABLE_SERVICES"),
			tableWatchers:  os.Getenv("MYSQL_TABLE_WATCHERS"),
		}
	})

	return instance
}

func (c *mysql) Host() string {
	return c.host
}

func (c *mysql) Port() string {
	return c.port
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

func (c *mysql) MaxConnections() int {
	return c.maxConnections
}

func (c *mysql) TableServices() string {
	return c.tableServices
}

func (c *mysql) TableWatchers() string {
	return c.tableWatchers
}
