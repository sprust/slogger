package trace_sharding_service

import (
	"context"
	"fmt"
	"log/slog"
	"os"
	"slogger_receiver/pkg/foundation/errs"
	"strings"
	"sync"

	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
)

const traceTreesViewName = "_traceTreesView"

var once sync.Once
var instance *TraceShardingService

// TODO: collections TTL

func Get() *TraceShardingService {
	once.Do(func() {
		instance = &TraceShardingService{
			collections: make(map[string]*mongo.Collection),
		}
	})

	return instance
}

type TraceShardingService struct {
	database    *mongo.Database
	mutex       sync.Mutex
	collections map[string]*mongo.Collection
}

func (s *TraceShardingService) InitCollection(ctx context.Context, loggedAt primitive.DateTime) (*mongo.Collection, error) {
	s.mutex.Lock()
	defer s.mutex.Unlock()

	collName := s.makeCollNameByDate(loggedAt)

	coll := s.collections[collName]

	if coll != nil {
		return coll, nil
	}

	coll, err := s.initColl(ctx, collName)

	if err != nil {
		return nil, errs.Err(err)
	}

	s.collections[collName] = coll

	return coll, nil
}

func (s *TraceShardingService) initColl(ctx context.Context, collName string) (*mongo.Collection, error) {
	if s.database == nil {
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
			return nil, errs.Err(err)
		}

		s.database = client.Database(os.Getenv("MONGODB_DB_PERIODIC_TRACES"))
	}

	collNames, err := s.database.ListCollectionNames(ctx, bson.M{"name": collName})

	if err != nil {
		return nil, err
	}

	coll := s.database.Collection(collName)

	for _, exCollName := range collNames {
		if exCollName == collName {
			return coll, nil
		}
	}

	err = s.addIndexesToColl(ctx, coll)

	if err != nil {
		return nil, err
	}

	err = s.freshTraceTreesView(ctx)

	if err != nil {
		return nil, err
	}

	slog.Info("Created collection: " + collName)

	return coll, nil
}

func (s *TraceShardingService) addIndexesToColl(ctx context.Context, coll *mongo.Collection) error {
	indexFields := []string{"sid", "tid", "ptid", "tp", "st", "tgs.nm", "lat"}

	for _, field := range indexFields {
		indexModel := mongo.IndexModel{
			Keys: bson.D{{Key: field, Value: 1}},
		}

		_, err := coll.Indexes().CreateOne(ctx, indexModel)

		if err != nil && !mongo.IsDuplicateKeyError(err) {
			return errs.Err(err)
		}
	}

	latIdIndexModel := mongo.IndexModel{
		Keys: bson.D{
			{Key: "lat", Value: -1},
			{Key: "_id", Value: 1},
		},
	}

	_, err := coll.Indexes().CreateOne(ctx, latIdIndexModel)

	if err != nil && !mongo.IsDuplicateKeyError(err) {
		return errs.Err(err)
	}

	sidTidIndexModel := mongo.IndexModel{
		Keys: bson.D{
			{Key: "sid", Value: 1},
			{Key: "tid", Value: 1},
		},
	}

	_, err = coll.Indexes().CreateOne(ctx, sidTidIndexModel)

	if err != nil && !mongo.IsDuplicateKeyError(err) {
		return errs.Err(err)
	}

	return nil
}

func (s *TraceShardingService) makeCollNameByDate(dt primitive.DateTime) string {
	t := dt.Time().UTC()

	date := t.Format("2006_01_02")

	hourFrom := t.Hour()
	hourTo := hourFrom + 1

	hourFromFormatted := fmt.Sprintf("%02d", hourFrom)
	hourToFormatted := fmt.Sprintf("%02d", hourTo)

	return fmt.Sprintf("traces_%s_%s_%s", date, hourFromFormatted, hourToFormatted)
}

func (s *TraceShardingService) freshTraceTreesView(ctx context.Context) error {
	collectionNames, err := s.detectCollectionNames(ctx)

	if err != nil {
		return errs.Err(err)
	}

	if len(collectionNames) == 0 {
		return nil
	}

	pipeline := bson.A{}

	project := bson.D{
		{Key: "tid", Value: 1},
		{Key: "ptid", Value: 1},
	}

	first := true
	var mainCollectionName string

	for _, collectionName := range collectionNames {
		set := bson.D{
			{Key: "$set", Value: bson.D{
				{Key: "__cn", Value: collectionName},
			}},
		}

		if first {
			mainCollectionName = collectionName

			pipeline = append(pipeline, bson.D{
				{Key: "$project", Value: project},
			})

			pipeline = append(pipeline, set)

			first = false

			continue
		}

		pipeline = append(pipeline, bson.D{
			{Key: "$unionWith", Value: bson.D{
				{Key: "coll", Value: collectionName},
				{Key: "pipeline", Value: bson.A{
					bson.D{{Key: "$project", Value: project}},
					set,
				}},
			}},
		})
	}

	collNames, err := s.database.ListCollectionNames(ctx, bson.M{"name": traceTreesViewName})

	if err != nil {
		return errs.Err(err)
	}

	exists := false

	for _, name := range collNames {
		if name == traceTreesViewName {
			exists = true
			break
		}
	}

	var operation string

	if exists {
		operation = "collMod"
	} else {
		operation = "create"
	}

	command := bson.D{
		{Key: operation, Value: traceTreesViewName},
		{Key: "viewOn", Value: mainCollectionName},
		{Key: "pipeline", Value: pipeline},
	}

	err = s.database.RunCommand(ctx, command).Err()

	if err != nil {
		return errs.Err(err)
	}

	slog.Info(fmt.Sprintf("Trace trees view '%s' %s successfully", traceTreesViewName, map[bool]string{true: "updated", false: "created"}[exists]))

	return nil
}

func (s *TraceShardingService) detectCollectionNames(ctx context.Context) ([]string, error) {
	if s.database == nil {
		return nil, errs.Err(fmt.Errorf("database not initialized"))
	}

	collNames, err := s.database.ListCollectionNames(ctx, bson.M{})

	if err != nil {
		return nil, errs.Err(err)
	}

	var traceCollections []string

	for _, name := range collNames {
		if strings.HasPrefix(name, "traces_") {
			traceCollections = append(traceCollections, name)
		}
	}

	return traceCollections, nil
}
