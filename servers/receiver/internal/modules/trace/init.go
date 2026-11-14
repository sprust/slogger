package trace

import (
	"context"
	"slogger/internal/dto"
	"slogger/internal/modules/trace/entities"
	"slogger/internal/modules/trace/internal/domain/actions/mutations/saveTraceAction"
	"slogger/internal/modules/trace/internal/errors"
	"sync"
)

var once sync.Once
var instance *Trace

type Trace struct {
	saveAction *saveTraceAction.Action
}

func Get() *Trace {
	once.Do(func() {
		instance = &Trace{
			saveAction: saveTraceAction.Get(),
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
