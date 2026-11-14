package config

import (
	"slogger_receiver/internal/config/internal/clickhouseConfig"
	"slogger_receiver/internal/config/internal/mysqlConfig"
	"sync"
)

var once sync.Once
var instance Config

type Config interface {
	Mysql() mysqlConfig.Mysql
	Clickhouse() clickhouseConfig.Config
}

type config struct {
	mysql      mysqlConfig.Mysql
	clickhouse clickhouseConfig.Config
}

func Get() Config {
	once.Do(func() {
		instance = &config{
			mysql:      mysqlConfig.Get(),
			clickhouse: clickhouseConfig.Get(),
		}
	})

	return instance
}

func (c *config) Mysql() mysqlConfig.Mysql {
	return c.mysql
}

func (c *config) Clickhouse() clickhouseConfig.Config {
	return c.clickhouse
}
