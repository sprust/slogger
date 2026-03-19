package buffer_service

import (
	"context"
	"encoding/json"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/repositories/buffer_repository"
	"slogger_receiver/pkg/foundation/errs"
	"sync"
)

var instance *Service
var once sync.Once

func Get() *Service {
	once.Do(func() {
		instance = &Service{
			repository: buffer_repository.Get(),
		}
	})

	return instance
}

type Service struct {
	repository *buffer_repository.Repository
}

func (s *Service) Save(ctx context.Context, serviceId int, traces *dto.TracesMessage) error {
	if traces.Creating != "" {
		var creating []dto.TraceCreating

		err := json.Unmarshal([]byte(traces.Creating), &creating)

		if err != nil {
			return errs.Err(err)
		}

		err = s.repository.InsertCreatingTraces(ctx, serviceId, creating)

		if err != nil {
			return errs.Err(err)
		}
	}

	if traces.Updating != "" {
		var updating []dto.TraceUpdating

		err := json.Unmarshal([]byte(traces.Updating), &updating)

		if err != nil {
			return errs.Err(err)
		}

		err = s.repository.InsertUpdatingTraces(ctx, serviceId, updating)

		if err != nil {
			return errs.Err(err)
		}
	}

	return nil
}
