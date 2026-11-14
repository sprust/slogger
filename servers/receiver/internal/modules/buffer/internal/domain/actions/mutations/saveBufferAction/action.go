package saveBufferAction

import (
	"context"
	"encoding/json"
	"slogger/internal/dto"
	"slogger/internal/modules/buffer/internal/deps"
	"slogger/internal/modules/buffer/internal/errors"
	"slogger/internal/modules/buffer/internal/repository"
	"slogger/pkg/foundation/errs"
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

func (a *Action) Handle(ctx context.Context, serviceId int, traces *dto.TracesMessage) errors.Error {
	if traces.Creating != "" {
		var creating []dto.TraceCreating

		err := json.Unmarshal([]byte(traces.Creating), &creating)

		if err != nil {
			return errors.Internal(errs.Err(err))
		}

		err = a.repository.InsertCreatingTraces(ctx, serviceId, creating)

		if err != nil {
			return errors.Internal(errs.Err(err))
		}
	}

	if traces.Updating != "" {
		var updating []dto.TraceUpdating

		err := json.Unmarshal([]byte(traces.Updating), &updating)

		if err != nil {
			return errors.Internal(errs.Err(err))
		}

		err = a.repository.InsertUpdatingTraces(ctx, serviceId, updating)

		if err != nil {
			return errors.Internal(errs.Err(err))
		}
	}

	return nil
}
