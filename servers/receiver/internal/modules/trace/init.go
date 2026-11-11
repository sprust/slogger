package trace

import (
	"context"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/modules/trace/entities"
	"slogger_receiver/internal/modules/trace/errors"
	"slogger_receiver/internal/modules/trace/internal/domain/actions/mutations/insert_action"
	"slogger_receiver/internal/modules/trace/internal/domain/actions/mutations/save_action"
	"sync"
)

var once sync.Once
var instance Service
var _ Service = (*serviceImpl)(nil)

type serviceImpl struct {
	saveAction   *save_action.Action
	insertAction *insert_action.Action
}

func Get() Service {
	once.Do(func() {
		instance = &serviceImpl{
			saveAction:   save_action.Get(),
			insertAction: insert_action.Get(),
		}
	})

	return instance
}

func (s *serviceImpl) Save(ctx context.Context, batch map[int]*dto.ServiceTraces) (entities.Result, errors.TraceError) {
	result, err := s.saveAction.Handle(ctx, batch)

	if err != nil {
		return result, errors.Internal(err)
	}

	return result, nil
}

func (s *serviceImpl) Insert(ctx context.Context, rows []entities.Row) errors.TraceError {
	return s.insertAction.Handle(ctx, rows)
}
