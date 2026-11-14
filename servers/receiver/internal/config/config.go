package config

import (
	"slogger_receiver/internal/config/internal/mysqlConfig"
	"sync"
)

var once sync.Once
var instance Config

type Config interface {
	Mysql() mysqlConfig.Mysql
}

type config struct {
	mysql mysqlConfig.Mysql
}

func Get() Config {
	once.Do(func() {
		instance = &config{
			mysql: mysqlConfig.Get(),
		}
	})

	return instance
}

func (c *config) Mysql() mysqlConfig.Mysql {
	return c.mysql
}
