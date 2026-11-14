package mongodb

import (
	"context"
	"fmt"
	"slogger/internal/config"
	"slogger/pkg/foundation/errs"
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
		conf := config.Get().Mongodb()

		instance = New(
			conf.Host(),
			conf.Port(),
			conf.DatabaseTraces(),
			conf.Username(),
			conf.Password(),
		)
	})

	return instance
}

func New(
	host string,
	port string,
	databaseName string,
	username string,
	password string,
) *Connection {
	uri := fmt.Sprintf(
		"mongodb://%s:%s@%s:%s",
		username,
		password,
		host,
		port,
	)

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
