package watcher_timeline_repository

import (
	"context"
	"slogger_receiver/internal/platform/mongodb"
	"slogger_receiver/pkg/foundation/errs"
	"time"

	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
)

// MaxBuckets caps the line on this side, independently of the panel's trimming.
//
// Without it a stopped panel means an array that grows until the document reaches 16MB,
// after which every push is refused and the line stops silently. A TTL cannot cover this:
// it removes documents, not elements of an array. 720 buckets of 15 seconds is three
// hours, which is more than the longest window the settings can ask for.
//
// Exported because the service trims what it hands over to the same number: what $slice
// would throw away should not be built into the update document in the first place.
const MaxBuckets = 720

// Group is a bucket's rollup: the traces of one shape, and the slowest of them by name
// and by the moment it started.
//
// TraceLoggedAt is left out of the document for a group whose traces have not finished:
// there is no slowest trace to date yet.
type Group struct {
	ServiceId     int                `bson:"sid"`
	Type          string             `bson:"tp"`
	Tags          []string           `bson:"tgs"`
	Count         int                `bson:"c"`
	DurCount      int                `bson:"dc"`
	DurSum        float64            `bson:"dSum"`
	DurMax        float64            `bson:"dMax"`
	TraceId       string             `bson:"tid"`
	TraceLoggedAt primitive.DateTime `bson:"tlat,omitempty"`
}

// Bucket is 15 seconds of one watcher's line.
//
// Count is traces started; DurCount is those of them whose duration is already known.
// They are separate because an average over Count would be diluted by every trace still
// running.
type Bucket struct {
	At       primitive.DateTime `bson:"t"`
	Count    int                `bson:"c"`
	DurCount int                `bson:"dc"`
	DurSum   float64            `bson:"dSum"`
	DurMax   float64            `bson:"dMax"`
	Groups   []Group            `bson:"g"`
}

type Repository interface {
	Push(ctx context.Context, buckets map[int][]Bucket) error
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

// Push appends buckets to the lines of the watchers they belong to, one document per
// watcher.
//
// The write is additive on purpose: a bucket that a late trace reopened arrives as a
// second element with the same `t`, and the panel sums those on read. Nothing here reads
// the document first, so two receivers — or a receiver and its own restart — cannot lose
// each other's counts.
func (r *MongoRepository) Push(ctx context.Context, buckets map[int][]Bucket) error {
	if len(buckets) == 0 {
		return nil
	}

	database, err := r.conn.DB(ctx)

	if err != nil {
		return errs.Err(err)
	}

	now := primitive.NewDateTimeFromTime(time.Now().UTC())

	models := make([]mongo.WriteModel, 0, len(buckets))

	for watcherId, watcherBuckets := range buckets {
		if len(watcherBuckets) == 0 {
			continue
		}

		models = append(
			models,
			mongo.NewUpdateOneModel().
				SetFilter(bson.M{"_id": watcherId}).
				SetUpdate(bson.M{
					"$push": bson.M{
						"tl": bson.M{
							// Sorted on the way in, so a late bucket lands in its place
							// rather than at the end: the panel reads windows off this
							// array and should not have to sort it.
							"$each":  watcherBuckets,
							"$sort":  bson.M{"t": 1},
							"$slice": -MaxBuckets,
						},
					},
					"$set": bson.M{"uat": now},
				}).
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
