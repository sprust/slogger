package mongodbConfig

import (
	"os"
	"sync"
)

var once sync.Once
var instance Config

type Config interface {
	Host() string
	Port() string
	Username() string
	Password() string
	DatabaseTraces() string
	CollectionBuffer() string
	CollectionInvalidBuffer() string
	CollectionTraceMetrics() string
	CollectionWatcherTimelines() string
}

type mongodb struct {
	host                       string
	port                       string
	username                   string
	password                   string
	databaseTraces             string
	collectionBuffer           string
	collectionInvalidBuffer    string
	collectionTraceMetrics     string
	collectionWatcherTimelines string
}

func Get() Config {
	once.Do(func() {
		collectionTraceMetrics := os.Getenv("MONGODB_COLL_TRACE_METRICS")

		if collectionTraceMetrics == "" {
			collectionTraceMetrics = "traceMetrics"
		}

		collectionWatcherTimelines := os.Getenv("MONGODB_COLL_WATCHER_TIMELINES")

		if collectionWatcherTimelines == "" {
			collectionWatcherTimelines = "watcherTimelines"
		}

		instance = &mongodb{
			host:                       os.Getenv("MONGODB_HOST"),
			port:                       os.Getenv("MONGODB_PORT"),
			username:                   os.Getenv("MONGODB_USERNAME"),
			password:                   os.Getenv("MONGODB_PASSWORD"),
			databaseTraces:             os.Getenv("MONGODB_DB_TRACES"),
			collectionBuffer:           os.Getenv("MONGODB_COLL_BUFFER"),
			collectionInvalidBuffer:    os.Getenv("MONGODB_COLL_INVALID_BUFFER"),
			collectionTraceMetrics:     collectionTraceMetrics,
			collectionWatcherTimelines: collectionWatcherTimelines,
		}
	})

	return instance
}

func (c *mongodb) Host() string {
	return c.host
}

func (c *mongodb) Port() string {
	return c.port
}

func (c *mongodb) Username() string {
	return c.username
}

func (c *mongodb) Password() string {
	return c.password
}

func (c *mongodb) DatabaseTraces() string {
	return c.databaseTraces
}

func (c *mongodb) CollectionBuffer() string {
	return c.collectionBuffer
}

func (c *mongodb) CollectionInvalidBuffer() string {
	return c.collectionInvalidBuffer
}

func (c *mongodb) CollectionTraceMetrics() string {
	return c.collectionTraceMetrics
}

func (c *mongodb) CollectionWatcherTimelines() string {
	return c.collectionWatcherTimelines
}
