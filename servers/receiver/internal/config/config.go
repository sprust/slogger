package config

import (
	"slogger_receiver/internal/config/internal/clickhouseConfig"
	"slogger_receiver/internal/config/internal/mongodbConfig"
	"slogger_receiver/internal/config/internal/mysqlConfig"
	"sync"
)

var once sync.Once
var instance Config

type Config interface {
	Mongodb() mongodbConfig.Config
	Mysql() mysqlConfig.Config
	Clickhouse() clickhouseConfig.Config
}

type config struct {
	mongodb    mongodbConfig.Config
	mysql      mysqlConfig.Config
	clickhouse clickhouseConfig.Config
}

func Get() Config {
	once.Do(func() {
		instance = &config{
			mongodb:    mongodbConfig.Get(),
			mysql:      mysqlConfig.Get(),
			clickhouse: clickhouseConfig.Get(),
		}
	})

	return instance
}

func (c *config) Mysql() mysqlConfig.Config {
	return c.mysql
}

func (c *config) Clickhouse() clickhouseConfig.Config {
	return c.clickhouse
}

func (c *config) Mongodb() mongodbConfig.Config {
	return c.mongodb
}
