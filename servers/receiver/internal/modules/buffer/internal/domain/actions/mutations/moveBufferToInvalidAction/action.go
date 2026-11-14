package moveBufferToInvalidAction

import (
	"context"
	"slogger_receiver/internal/modules/buffer/entities"
	"slogger_receiver/internal/modules/buffer/internal/deps"
	"slogger_receiver/internal/modules/buffer/internal/errors"
	"slogger_receiver/internal/modules/buffer/internal/repository"
	"sync"
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

func (a *Action) Handle(ctx context.Context, docs []entities.InvalidDoc) errors.Error {
	if err := a.repository.MoveToInvalid(ctx, docs); err != nil {
		return errors.Internal(err)
	}

	return nil
}
