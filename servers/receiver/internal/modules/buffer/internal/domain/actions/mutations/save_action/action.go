package save_action

import (
	"context"
	"encoding/json"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/modules/buffer/errors"
	"slogger_receiver/internal/modules/buffer/internal/repository"
	"slogger_receiver/pkg/foundation/errs"
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

func (a *Action) Handle(ctx context.Context, serviceId int, traces *dto.TracesMessage) errors.BufferError {
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
