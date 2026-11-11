package move_to_invalid_action

import (
	"context"
	"slogger_receiver/internal/modules/buffer/entities"
	"slogger_receiver/internal/modules/buffer/errors"
	"slogger_receiver/internal/modules/buffer/internal/repository"
	"sync"
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

func (a *Action) Handle(ctx context.Context, docs []entities.InvalidDoc) errors.BufferError {
	if err := a.repository.MoveToInvalid(ctx, docs); err != nil {
		return errors.Internal(err)
	}

	return nil
}
