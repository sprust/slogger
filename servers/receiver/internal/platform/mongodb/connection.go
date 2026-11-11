package mongodb

import (
	"context"
	"fmt"
	"os"
	"slogger_receiver/pkg/foundation/errs"
	"sync"

	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
)

var once sync.Once
var instance *Connection

type Connection struct {
	uri          string
	databaseName string

	database *mongo.Database
	mutex    sync.Mutex
}

func Get() *Connection {
	once.Do(func() {
		instance = New(os.Getenv("MONGODB_DB_TRACES"))
	})

	return instance
}

func New(databaseName string) *Connection {
	uri := fmt.Sprintf(
		"mongodb://%s:%s@%s:%s",
		os.Getenv("MONGODB_USERNAME"),
		os.Getenv("MONGODB_PASSWORD"),
		os.Getenv("MONGODB_HOST"),
		os.Getenv("MONGODB_PORT"),
	)

	return NewWithURI(uri, databaseName)
}

func NewPeriodic() *Connection {
	databaseName := os.Getenv("MONGODB_DB_PERIODIC_TRACES")

	if databaseName == "" {
		databaseName = "tracesPeriodic"
	}

	return New(databaseName)
}

func NewWithURI(uri string, databaseName string) *Connection {
	return &Connection{
		uri:          uri,
		databaseName: databaseName,
	}
}

func (c *Connection) DB(ctx context.Context) (*mongo.Database, error) {
	c.mutex.Lock()
	defer c.mutex.Unlock()

	if c.database != nil {
		return c.database, nil
	}

	client, err := mongo.Connect(ctx, options.Client().ApplyURI(c.uri))

	if err != nil {
		return nil, errs.Err(err)
	}

	c.database = client.Database(c.databaseName)

	return c.database, nil
}

func (c *Connection) Close(ctx context.Context) error {
	c.mutex.Lock()
	defer c.mutex.Unlock()

	if c.database == nil {
		return nil
	}

	err := c.database.Client().Disconnect(ctx)

	c.database = nil

	if err != nil {
		return errs.Err(err)
	}

	return nil
}
