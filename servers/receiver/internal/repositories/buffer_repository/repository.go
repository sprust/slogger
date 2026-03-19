package buffer_repository

import (
	"context"
	"fmt"
	"os"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/helpers/datetime_helper"
	"slogger_receiver/pkg/foundation/errs"
	"sync"

	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
)

var instance *Repository
var once sync.Once

func Get() *Repository {
	once.Do(func() {
		instance = &Repository{}
	})

	return instance
}

type Repository struct {
	mColl *mongo.Collection
}

func (r *Repository) InsertCreatingTraces(ctx context.Context, serviceId int, traces []dto.TraceCreating) error {
	err := instance.connect(ctx)

	if err != nil {
		return errs.Err(err)
	}

	for _, trace := range traces {
		err := r.insertCreatingTrace(ctx, serviceId, trace)

		if err != nil {
			return errs.Err(err)
		}
	}

	return nil
}

func (r *Repository) InsertUpdatingTraces(ctx context.Context, serviceId int, traces []dto.TraceUpdating) error {
	err := instance.connect(ctx)

	if err != nil {
		return errs.Err(err)
	}

	for _, trace := range traces {
		err := r.insertUpdatingTrace(ctx, serviceId, trace)

		if err != nil {
			return errs.Err(err)
		}
	}

	return nil
}

func (r *Repository) insertCreatingTrace(ctx context.Context, serviceId int, trace dto.TraceCreating) error {
	doc := bson.M{
		"op":  "c",
		"sid": serviceId,
		"tid": trace.TraceId,
		"tp":  trace.Type,
		"st":  trace.Status,
		"tgs": trace.Tags,
		"dt":  trace.Data,
		"lat": datetime_helper.ConvertLoggedAt(trace.LoggedAt),
		"cat": datetime_helper.Now(),
	}

	if trace.ParentTraceId != nil {
		doc["ptid"] = trace.ParentTraceId
	}

	if trace.Duration != nil {
		doc["dur"] = trace.Duration
	}

	if trace.Memory != nil {
		doc["mem"] = trace.Memory
	}

	if trace.Cpu != nil {
		doc["cpu"] = trace.Cpu
	}

	_, err := r.mColl.InsertOne(ctx, doc)

	if err != nil {
		return errs.Err(err)
	}

	return nil
}

func (r *Repository) insertUpdatingTrace(ctx context.Context, serviceId int, trace dto.TraceUpdating) error {
	doc := bson.M{
		"op":   "u",
		"sid":  serviceId,
		"tid":  trace.TraceId,
		"st":   trace.Status,
		"dt":   trace.Data,
		"plat": datetime_helper.ConvertLoggedAt(trace.ParentLoggedAt),
		"cat":  datetime_helper.Now(),
	}

	if trace.Tags != nil {
		doc["tgs"] = trace.Tags
	}

	if trace.Duration != nil {
		doc["dur"] = trace.Duration
	}

	if trace.Memory != nil {
		doc["mem"] = trace.Memory
	}

	if trace.Cpu != nil {
		doc["cpu"] = trace.Cpu
	}

	_, err := r.mColl.InsertOne(ctx, doc)

	if err != nil {
		return errs.Err(err)
	}

	return nil
}

func (r *Repository) connect(ctx context.Context) error {
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

	opt := options.Client().ApplyURI(url)

	var err error

	client, err := mongo.Connect(ctx, opt)

	if err != nil {
		return errs.Err(err)
	}

	r.mColl = client.
		Database(os.Getenv("MONGODB_DB_TRACES")).
		Collection(os.Getenv("MONGODB_COLL_BUFFER"))

	return nil
}
