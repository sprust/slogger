package deleteBufferByIdsAction

import (
	"context"
	"slogger/internal/modules/buffer/internal/deps"
	"slogger/internal/modules/buffer/internal/errors"
	"slogger/internal/modules/buffer/internal/repository"
	"sync"

	"go.mongodb.org/mongo-driver/bson/primitive"
)

var once sync.Once
var instance *Action

type Action struct {
	repository repository.BufferRepository
}

func Get() *Action {
	once.Do(func() {
		instance = New(
			deps.Get().BufferRepository,
		)
	})

	return instance
}

func New(repository repository.BufferRepository) *Action {
	return &Action{
		repository: repository,
	}
}

func (a *Action) Handle(ctx context.Context, ids []primitive.ObjectID) (int64, errors.Error) {
	count, err := a.repository.DeleteByIds(ctx, ids)

	if err != nil {
		return 0, errors.Internal(err)
	}

	return count, nil
}
