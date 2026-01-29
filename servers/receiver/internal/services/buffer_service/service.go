package buffer_service

import (
	"context"
	"encoding/json"
	"fmt"
	"os"
	"slogger_receiver/internal/dto"
	"slogger_receiver/pkg/foundation/errs"
	"sync"

	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
)

var instance *Service
var once sync.Once

func Get() *Service {
	once.Do(func() {
		instance = &Service{}
	})

	return instance
}

type Service struct {
	mColl *mongo.Collection
}

func (s *Service) Save(ctx context.Context, serviceId int, traces *dto.TracesMessage) error {
	err := instance.connect(ctx)

	if err != nil {
		return errs.Err(err)
	}

	if traces.Creating != "" {
		var creating []dto.TraceCreating

		err = json.Unmarshal([]byte(traces.Creating), &creating)

		if err != nil {
			return errs.Err(err)
		}

		err = s.insertCreatingTraces(ctx, serviceId, creating)

		if err != nil {
			return errs.Err(err)
		}
	}

	if traces.Updating != "" {
		var updating []dto.TraceUpdating

		err = json.Unmarshal([]byte(traces.Updating), &updating)

		if err != nil {
			return errs.Err(err)
		}

		err = s.insertUpdatingTraces(ctx, serviceId, updating)

		if err != nil {
			return errs.Err(err)
		}
	}

	return nil
}

func (s *Service) connect(ctx context.Context) error {
	if s.mColl != nil {
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

	s.mColl = client.
		Database(os.Getenv("MONGODB_DB_BUFFER")).
		Collection(os.Getenv("MONGODB_COLL_BUFFER"))

	return nil
}
