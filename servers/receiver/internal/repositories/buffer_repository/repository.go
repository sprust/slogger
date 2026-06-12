package buffer_repository

import (
	"context"
	"fmt"
	"log/slog"
	"os"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/helpers/datetime_helper"
	"slogger_receiver/pkg/foundation/errs"
	"sync"
	"time"

	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
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
	mColl        *mongo.Collection
	mInvalidColl *mongo.Collection
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

func (r *Repository) FindMany(ctx context.Context, limit int) (map[int]*dto.ServiceTraces, error) {
	err := instance.connect(ctx)

	if err != nil {
		return nil, errs.Err(err)
	}

	cursor, err := r.mColl.Find(
		ctx,
		bson.M{
			"op": bson.M{
				"$in": []string{"c", "u"},
			},
		},
		options.Find().
			SetLimit(int64(limit)).
			SetSort(bson.D{{"cat", 1}}),
	)

	if err != nil {
		return nil, errs.Err(err)
	}

	defer func(cursor *mongo.Cursor, ctx context.Context) {
		err := cursor.Close(ctx)

		if err != nil {
			slog.Error(errs.Err(err).Error())
		}

	}(cursor, ctx)

	result := make(map[int]*dto.ServiceTraces)

	for cursor.Next(ctx) {
		var doc bson.M

		if err := cursor.Decode(&doc); err != nil {
			return nil, errs.Err(err)
		}

		serviceId := int(doc["sid"].(int32))
		op := doc["op"].(string)
		traceId := asString(doc["tid"])

		if result[serviceId] == nil {
			result[serviceId] = &dto.ServiceTraces{}
		}

		if op == "c" {
			trace := &dto.TraceCreating{
				TraceId:  asString(doc["tid"]),
				Type:     asString(doc["tp"]),
				Status:   asString(doc["st"]),
				Tags:     asInterfaces(doc["tgs"]),
				Data:     doc["dt"],
				LoggedAt: asLoggedAtString(doc["lat"]),
			}

			if ptid, ok := doc["ptid"]; ok {
				ptidStr := asString(ptid)
				trace.ParentTraceId = &ptidStr
			}

			if dur, ok := doc["dur"]; ok {
				durFloat := asFloat64(dur)
				trace.Duration = &durFloat
			}

			if mem, ok := doc["mem"]; ok {
				memFloat := asFloat64(mem)
				trace.Memory = &memFloat
			}

			if cpu, ok := doc["cpu"]; ok {
				cpuFloat := asFloat64(cpu)
				trace.Cpu = &cpuFloat
			}

			result[serviceId].AddCreating(trace)

			if id, ok := doc["_id"].(primitive.ObjectID); ok {
				result[serviceId].AddId(traceId, id)
			}
		} else if op == "u" {
			trace := &dto.TraceUpdating{
				TraceId:        asString(doc["tid"]),
				Status:         asString(doc["st"]),
				Data:           doc["dt"],
				ParentLoggedAt: doc["plat"],
			}

			if tgs, ok := doc["tgs"]; ok {
				tgsItems := asInterfaces(tgs)
				trace.Tags = &tgsItems
			}

			if dur, ok := doc["dur"]; ok {
				durFloat := asFloat64(dur)
				trace.Duration = &durFloat
			}

			if mem, ok := doc["mem"]; ok {
				memFloat := asFloat64(mem)
				trace.Memory = &memFloat
			}

			if cpu, ok := doc["cpu"]; ok {
				cpuFloat := asFloat64(cpu)
				trace.Cpu = &cpuFloat
			}

			result[serviceId].AddUpdating(trace)

			if id, ok := doc["_id"].(primitive.ObjectID); ok {
				result[serviceId].AddId(traceId, id)
			}
		}
	}

	if err := cursor.Err(); err != nil {
		return nil, errs.Err(err)
	}

	return result, nil
}

func (r *Repository) DeleteByIds(ctx context.Context, ids []primitive.ObjectID) (int64, error) {
	if len(ids) == 0 {
		return 0, nil
	}

	err := instance.connect(ctx)

	if err != nil {
		return 0, errs.Err(err)
	}

	many, err := r.mColl.DeleteMany(ctx, bson.M{"_id": bson.M{"$in": ids}})

	if err != nil {
		return 0, errs.Err(err)
	}

	return many.DeletedCount, nil
}

// MarkFailed increments the save-attempt counter for the given buffer documents
// and moves to the invalid-buffer collection those that reached maxAttempts.
func (r *Repository) MarkFailed(ctx context.Context, ids []primitive.ObjectID, maxAttempts int) error {
	if len(ids) == 0 {
		return nil
	}

	err := instance.connect(ctx)

	if err != nil {
		return errs.Err(err)
	}

	_, err = r.mColl.UpdateMany(
		ctx,
		bson.M{"_id": bson.M{"$in": ids}},
		bson.M{"$inc": bson.M{"att": 1}},
	)

	if err != nil {
		return errs.Err(err)
	}

	cursor, err := r.mColl.Find(
		ctx,
		bson.M{
			"_id": bson.M{"$in": ids},
			"att": bson.M{"$gte": maxAttempts},
		},
	)

	if err != nil {
		return errs.Err(err)
	}

	defer func(cursor *mongo.Cursor, ctx context.Context) {
		if err := cursor.Close(ctx); err != nil {
			slog.Error(errs.Err(err).Error())
		}
	}(cursor, ctx)

	invalidDocs := make([]interface{}, 0)
	invalidIds := make([]primitive.ObjectID, 0)

	for cursor.Next(ctx) {
		var doc bson.M

		if err := cursor.Decode(&doc); err != nil {
			return errs.Err(err)
		}

		if id, ok := doc["_id"].(primitive.ObjectID); ok {
			invalidIds = append(invalidIds, id)
		}

		doc["iat"] = datetime_helper.Now()

		invalidDocs = append(invalidDocs, doc)
	}

	if err := cursor.Err(); err != nil {
		return errs.Err(err)
	}

	if len(invalidDocs) == 0 {
		return nil
	}

	if _, err := r.mInvalidColl.InsertMany(ctx, invalidDocs); err != nil {
		return errs.Err(err)
	}

	if _, err := r.mColl.DeleteMany(ctx, bson.M{"_id": bson.M{"$in": invalidIds}}); err != nil {
		return errs.Err(err)
	}

	slog.Warn(fmt.Sprintf("moved %d buffer docs to invalid buffer after %d failed attempts", len(invalidIds), maxAttempts))

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
		"lat": trace.LoggedAt,
		"cat": datetime_helper.Now(),
	}

	if trace.ParentTraceId != nil {
		doc["ptid"] = *trace.ParentTraceId
	}

	if trace.Duration != nil {
		doc["dur"] = *trace.Duration
	}

	if trace.Memory != nil {
		doc["mem"] = *trace.Memory
	}

	if trace.Cpu != nil {
		doc["cpu"] = *trace.Cpu
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
		"plat": trace.ParentLoggedAt,
		"cat":  datetime_helper.Now(),
	}

	if trace.Tags != nil {
		doc["tgs"] = *trace.Tags
	}

	if trace.Duration != nil {
		doc["dur"] = *trace.Duration
	}

	if trace.Memory != nil {
		doc["mem"] = *trace.Memory
	}

	if trace.Cpu != nil {
		doc["cpu"] = *trace.Cpu
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

	database := client.Database(os.Getenv("MONGODB_DB_TRACES"))

	r.mColl = database.Collection(os.Getenv("MONGODB_COLL_BUFFER"))
	r.mInvalidColl = database.Collection(os.Getenv("MONGODB_COLL_INVALID_BUFFER"))

	return nil
}

func asString(value interface{}) string {
	if value == nil {
		return ""
	}

	if str, ok := value.(string); ok {
		return str
	}

	return fmt.Sprint(value)
}

func asInterfaces(value interface{}) []interface{} {
	if value == nil {
		return []interface{}{}
	}

	if items, ok := value.([]interface{}); ok {
		return items
	}

	if items, ok := value.(primitive.A); ok {
		return []interface{}(items)
	}

	return []interface{}{value}
}

func asFloat64(value interface{}) float64 {
	switch v := value.(type) {
	case float64:
		return v
	case float32:
		return float64(v)
	case int:
		return float64(v)
	case int32:
		return float64(v)
	case int64:
		return float64(v)
	case primitive.Decimal128:
		f, exp, err := v.BigInt()
		if err != nil || f == nil {
			return 0
		}

		return float64(f.Int64()) * float64Pow10(exp)
	default:
		return 0
	}
}

func asLoggedAtString(value interface{}) string {
	switch v := value.(type) {
	case primitive.DateTime:
		return v.Time().UTC().Format(time.RFC3339Nano)
	case time.Time:
		return v.UTC().Format(time.RFC3339Nano)
	case string:
		return v
	default:
		return ""
	}
}

func float64Pow10(exp int) float64 {
	result := 1.0

	if exp > 0 {
		for i := 0; i < exp; i++ {
			result *= 10
		}

		return result
	}

	for i := 0; i < -exp; i++ {
		result /= 10
	}

	return result
}
