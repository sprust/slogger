package pending_trace_repository

import (
	"context"
	"fmt"
	"os"
	"slogger_receiver/pkg/foundation/errs"
	"strconv"
	"sync"
	"time"

	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
)

const defaultCollection = "pendingTraces"

// defaultTtl is how long a trace waits for its other half. A job running longer than this
// still gets its update written: the transporter falls back to reading the trace from
// ClickHouse when it finds nothing here.
const defaultTtl = 3 * time.Hour

// PendingTrace is a trace that has come in halves and is waiting for the one still
// missing: a create whose update has not arrived, or an update that came before its
// create. It holds the merge of everything received so far.
type PendingTrace struct {
	ServiceId     int
	TraceId       string
	LoggedAtMicro int64
	ParentTraceId string
	Type          string
	Status        string
	Tags          []string
	RawData       string
	Duration      *float64
	Memory        *float64
	Cpu           *float64
	HasUpdate     bool
	// InsertedAtMicro is the uat of the row this trace already has in ClickHouse, 0 when it
	// has none: that row is deleted once the merged one replaces it.
	InsertedAtMicro int64
	CreatedAtMicro  int64
}

type document struct {
	Id              string    `bson:"_id"`
	ServiceId       int       `bson:"sid"`
	TraceId         string    `bson:"tid"`
	LoggedAtMicro   int64     `bson:"lat"`
	ParentTraceId   string    `bson:"ptid"`
	Type            string    `bson:"tp"`
	Status          string    `bson:"st"`
	Tags            []string  `bson:"tgs"`
	RawData         string    `bson:"dt"`
	Duration        *float64  `bson:"dur"`
	Memory          *float64  `bson:"mem"`
	Cpu             *float64  `bson:"cpu"`
	HasUpdate       bool      `bson:"hu"`
	InsertedAtMicro int64     `bson:"iuat"`
	CreatedAtMicro  int64     `bson:"cat"`
	UpdatedAt       time.Time `bson:"uat"`
}

var instance *Repository
var once sync.Once

func Get() *Repository {
	once.Do(func() {
		instance = &Repository{}
	})

	return instance
}

type Repository struct {
	mColl        *mongo.Collection
	connectMutex sync.Mutex
}

// Id is the document id of a trace: one document per service and trace.
func Id(serviceId int, traceId string) string {
	return strconv.Itoa(serviceId) + ":" + traceId
}

// FindMany reads the pending traces among ids in one query.
func (r *Repository) FindMany(ctx context.Context, ids []string) (map[string]PendingTrace, error) {
	result := make(map[string]PendingTrace)

	if len(ids) == 0 {
		return result, nil
	}

	if err := r.connect(ctx); err != nil {
		return nil, errs.Err(err)
	}

	cursor, err := r.mColl.Find(ctx, bson.M{"_id": bson.M{"$in": ids}})

	if err != nil {
		return nil, errs.Err(err)
	}

	defer func() {
		_ = cursor.Close(ctx)
	}()

	for cursor.Next(ctx) {
		var doc document

		if err := cursor.Decode(&doc); err != nil {
			return nil, errs.Err(err)
		}

		result[doc.Id] = PendingTrace{
			ServiceId:       doc.ServiceId,
			TraceId:         doc.TraceId,
			LoggedAtMicro:   doc.LoggedAtMicro,
			ParentTraceId:   doc.ParentTraceId,
			Type:            doc.Type,
			Status:          doc.Status,
			Tags:            doc.Tags,
			RawData:         doc.RawData,
			Duration:        doc.Duration,
			Memory:          doc.Memory,
			Cpu:             doc.Cpu,
			HasUpdate:       doc.HasUpdate,
			InsertedAtMicro: doc.InsertedAtMicro,
			CreatedAtMicro:  doc.CreatedAtMicro,
		}
	}

	if err := cursor.Err(); err != nil {
		return nil, errs.Err(err)
	}

	return result, nil
}

// Apply writes what a batch left waiting and forgets what it completed, in one bulk write.
func (r *Repository) Apply(ctx context.Context, save []PendingTrace, forget []string, now time.Time) error {
	if len(save) == 0 && len(forget) == 0 {
		return nil
	}

	if err := r.connect(ctx); err != nil {
		return errs.Err(err)
	}

	models := make([]mongo.WriteModel, 0, len(save)+len(forget))

	for _, trace := range save {
		id := Id(trace.ServiceId, trace.TraceId)

		models = append(
			models,
			mongo.NewReplaceOneModel().
				SetFilter(bson.M{"_id": id}).
				SetReplacement(document{
					Id:              id,
					ServiceId:       trace.ServiceId,
					TraceId:         trace.TraceId,
					LoggedAtMicro:   trace.LoggedAtMicro,
					ParentTraceId:   trace.ParentTraceId,
					Type:            trace.Type,
					Status:          trace.Status,
					Tags:            trace.Tags,
					RawData:         trace.RawData,
					Duration:        trace.Duration,
					Memory:          trace.Memory,
					Cpu:             trace.Cpu,
					HasUpdate:       trace.HasUpdate,
					InsertedAtMicro: trace.InsertedAtMicro,
					CreatedAtMicro:  trace.CreatedAtMicro,
					UpdatedAt:       now,
				}).
				SetUpsert(true),
		)
	}

	for _, id := range forget {
		models = append(models, mongo.NewDeleteOneModel().SetFilter(bson.M{"_id": id}))
	}

	if _, err := r.mColl.BulkWrite(ctx, models, options.BulkWrite().SetOrdered(false)); err != nil {
		return errs.Err(err)
	}

	return nil
}

func (r *Repository) connect(ctx context.Context) error {
	r.connectMutex.Lock()
	defer r.connectMutex.Unlock()

	if r.mColl != nil {
		return nil
	}

	url := fmt.Sprintf(
		"mongodb://%s:%s@%s:%s",
		os.Getenv("MONGODB_USERNAME"),
		os.Getenv("MONGODB_PASSWORD"),
		os.Getenv("MONGODB_HOST"),
		os.Getenv("MONGODB_PORT"),
	)

	client, err := mongo.Connect(ctx, options.Client().ApplyURI(url))

	if err != nil {
		return errs.Err(err)
	}

	collectionName := os.Getenv("MONGODB_COLL_PENDING_TRACES")

	if collectionName == "" {
		collectionName = defaultCollection
	}

	collection := client.Database(os.Getenv("MONGODB_DB_TRACES")).Collection(collectionName)

	ttl := defaultTtl

	if value := os.Getenv("PENDING_TRACES_TTL_SECONDS"); value != "" {
		seconds, err := strconv.Atoi(value)

		if err != nil || seconds <= 0 {
			return errs.Err(fmt.Errorf("PENDING_TRACES_TTL_SECONDS must be a positive number, got %q", value))
		}

		ttl = time.Duration(seconds) * time.Second
	}

	// A trace whose other half never comes is dropped silently: the half that came is
	// already in ClickHouse, or is an update with nothing to attach to.
	_, err = collection.Indexes().CreateOne(ctx, mongo.IndexModel{
		Keys:    bson.D{{Key: "uat", Value: 1}},
		Options: options.Index().SetExpireAfterSeconds(int32(ttl.Seconds())),
	})

	if err != nil {
		return errs.Err(err)
	}

	r.mColl = collection

	return nil
}
