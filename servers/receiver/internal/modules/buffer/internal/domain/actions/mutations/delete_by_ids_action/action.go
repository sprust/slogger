package delete_by_ids_action

import (
	"context"
	"slogger_receiver/internal/modules/buffer/errors"
	"slogger_receiver/internal/modules/buffer/internal/repository"
	"sync"

	"go.mongodb.org/mongo-driver/bson/primitive"
)

var once sync.Once
var instance *Action

type Action struct {
	repository *repository.MongoBufferRepository
}

func Get() *Action {
	once.Do(func() {
		instance = &Action{
			repository: repository.Get(),
		}
	})

	return instance
}

func (a *Action) Handle(ctx context.Context, ids []primitive.ObjectID) (int64, errors.BufferError) {
	count, err := a.repository.DeleteByIds(ctx, ids)

	if err != nil {
		return 0, errors.Internal(err)
	}

	return count, nil
}
