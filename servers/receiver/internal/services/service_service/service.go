package service_service

import (
	"context"
	"errors"
	"slogger_receiver/internal/repositories/service_repository"
	"sync"
)

var once sync.Once
var instance *Service

func Get() *Service {
	once.Do(func() {
		instance = &Service{
			repository: service_repository.Get(),
		}
	})

	return instance
}

type Service struct {
	repository *service_repository.Repository
}

func (s *Service) GetIdByApiToken(ctx context.Context, apiToken string) (int, error) {
	serviceId, err := s.repository.FindIdByApiToken(ctx, apiToken)

	if err != nil {
		return 0, err
	}

	if serviceId == 0 {
		return 0, errors.New("service not found by token: " + apiToken)
	}

	return serviceId, nil
}

func (s *Service) Close() error {
	return s.repository.Close()
}
