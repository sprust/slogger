package insertTraceAction

import (
	"context"
	"slogger_receiver/internal/modules/trace/entities"
	"slogger_receiver/internal/modules/trace/internal/deps"
	"slogger_receiver/internal/modules/trace/internal/errors"
	"slogger_receiver/internal/modules/trace/internal/repository/trace_repository"
	"sync"
)

var once sync.Once
var instance *Action

type Action struct {
	repository trace_repository.Repository
}

func Get() *Action {
	once.Do(func() {
		instance = New(
			deps.Get().TraceRepository,
		)
	})

	return instance
}

func New(repository trace_repository.Repository) *Action {
	return &Action{
		repository: repository,
	}
}

func (a *Action) Handle(ctx context.Context, rows []entities.Row) errors.Error {
	if err := a.repository.Insert(ctx, rows); err != nil {
		return errors.Internal(err)
	}

	return nil
}
