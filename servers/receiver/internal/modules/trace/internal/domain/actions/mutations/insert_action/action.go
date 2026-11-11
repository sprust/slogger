package insert_action

import (
	"context"
	"slogger_receiver/internal/modules/trace/entities"
	"slogger_receiver/internal/modules/trace/errors"
	"slogger_receiver/internal/modules/trace/internal/repository/clickhouse_trace_repository"
	"sync"
)

var once sync.Once
var instance *Action

type Action struct {
	repository *clickhouse_trace_repository.Repository
}

func Get() *Action {
	once.Do(func() {
		instance = &Action{
			repository: clickhouse_trace_repository.Get(),
		}
	})

	return instance
}

func (a *Action) Handle(ctx context.Context, rows []entities.Row) errors.TraceError {
	if err := a.repository.Insert(ctx, rows); err != nil {
		return errors.Internal(err)
	}

	return nil
}
