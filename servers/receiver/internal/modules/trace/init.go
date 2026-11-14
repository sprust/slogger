package trace

import (
	"context"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/modules/trace/entities"
	"slogger_receiver/internal/modules/trace/internal/domain/actions/mutations/insertTraceAction"
	"slogger_receiver/internal/modules/trace/internal/domain/actions/mutations/saveTraceAction"
	"slogger_receiver/internal/modules/trace/internal/errors"
	"sync"
)

var once sync.Once
var instance *Trace

type Trace struct {
	saveAction   *saveTraceAction.Action
	insertAction *insertTraceAction.Action
}

func Get() *Trace {
	once.Do(func() {
		instance = &Trace{
			saveAction:   saveTraceAction.Get(),
			insertAction: insertTraceAction.Get(),
		}
	})

	return instance
}

func (s *Trace) Save(ctx context.Context, batch map[int]*dto.ServiceTraces) (entities.Result, errors.Error) {
	result, err := s.saveAction.Handle(ctx, batch)

	if err != nil {
		return result, errors.Internal(err)
	}

	return result, nil
}

func (s *Trace) Insert(ctx context.Context, rows []entities.Row) errors.Error {
	return s.insertAction.Handle(ctx, rows)
}
