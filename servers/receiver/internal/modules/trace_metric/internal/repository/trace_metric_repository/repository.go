package trace_metric_repository

import (
	"context"
	"slogger_receiver/internal/platform/mongodb"
	"slogger_receiver/pkg/foundation/errs"

	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
)

// Key is one document of the collection: a service, a trace type and the start of a
// fifteen-minute slot, in unix milliseconds so that equal moments are equal keys.
type Key struct {
	ServiceId int
	Type      string
	At        int64
}

// Counts is what the traces of one key added up to since the last push, one counter per
// clock a trace carries.
type Counts struct {
	Logged   int
	Buffered int
	Stored   int
}

type Repository interface {
	Push(ctx context.Context, counts map[Key]Counts) error
}

func NewMongoRepository(conn *mongodb.Connection, collectionName string) *MongoRepository {
	return &MongoRepository{
		conn:           conn,
		collectionName: collectionName,
	}
}

type MongoRepository struct {
	conn           *mongodb.Connection
	collectionName string
}

// Push adds the counts to their documents, creating the ones that are not there yet.
//
// Additive, and never read first, for the same reason as the watchers' lines: two
// receivers, or a receiver and its own restart, cannot lose each other's counts.
func (r *MongoRepository) Push(ctx context.Context, counts map[Key]Counts) error {
	if len(counts) == 0 {
		return nil
	}

	database, err := r.conn.DB(ctx)

	if err != nil {
		return errs.Err(err)
	}

	models := make([]mongo.WriteModel, 0, len(counts))

	for key, count := range counts {
		increments := bson.M{}

		if count.Logged > 0 {
			increments["lc"] = count.Logged
		}

		if count.Buffered > 0 {
			increments["bc"] = count.Buffered
		}

		if count.Stored > 0 {
			increments["sc"] = count.Stored
		}

		if len(increments) == 0 {
			continue
		}

		models = append(
			models,
			mongo.NewUpdateOneModel().
				SetFilter(bson.M{
					"sid": key.ServiceId,
					"tp":  key.Type,
					"t":   primitive.DateTime(key.At),
				}).
				SetUpdate(bson.M{"$inc": increments}).
				SetUpsert(true),
		)
	}

	if len(models) == 0 {
		return nil
	}

	_, err = database.Collection(r.collectionName).BulkWrite(ctx, models, options.BulkWrite().SetOrdered(false))

	if err != nil {
		return errs.Err(err)
	}

	return nil
}
