package config

import (
	"slogger/internal/config/internal/clickhouseConfig"
	"slogger/internal/config/internal/mongodbConfig"
	"slogger/internal/config/internal/mysqlConfig"
	"slogger/internal/config/internal/receiverConfig"
	"sync"
)

var once sync.Once
var instance Config

type Config interface {
	Receiver() receiverConfig.Config
	Mongodb() mongodbConfig.Config
	Mysql() mysqlConfig.Config
	Clickhouse() clickhouseConfig.Config
}

type config struct {
	receiver   receiverConfig.Config
	mongodb    mongodbConfig.Config
	mysql      mysqlConfig.Config
	clickhouse clickhouseConfig.Config
}

func Get() Config {
	once.Do(func() {
		instance = &config{
			receiver:   receiverConfig.Get(),
			mongodb:    mongodbConfig.Get(),
			mysql:      mysqlConfig.Get(),
			clickhouse: clickhouseConfig.Get(),
		}
	})

	return instance
}

func (c *config) Receiver() receiverConfig.Config {
	return c.receiver
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
