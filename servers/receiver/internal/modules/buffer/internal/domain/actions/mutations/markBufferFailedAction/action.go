package markBufferFailedAction

import (
	"context"
	"slogger_receiver/internal/modules/buffer/internal/deps"
	"slogger_receiver/internal/modules/buffer/internal/errors"
	"slogger_receiver/internal/modules/buffer/internal/repository"
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

func (a *Action) Handle(ctx context.Context, ids []primitive.ObjectID, maxAttempts int) errors.Error {
	if err := a.repository.MarkFailed(ctx, ids, maxAttempts); err != nil {
		return errors.Internal(err)
	}

	return nil
}
