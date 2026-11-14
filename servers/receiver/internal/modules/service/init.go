package service

import (
	"context"
	"slogger/internal/modules/service/internal/domain/actions/queries/findServiceByTokenAction"
	"slogger/internal/modules/service/internal/errors"
	"sync"
)

var once sync.Once
var instance *Service

type Service struct {
	findServiceIdByTokenAction *findServiceByTokenAction.Action
}

func Get() *Service {
	once.Do(func() {
		instance = &Service{
			findServiceIdByTokenAction: findServiceByTokenAction.Get(),
		}
	})

	return instance
}

func (s *Service) FindIdByToken(ctx context.Context, token string) (int, errors.Error) {
	return s.findServiceIdByTokenAction.Handle(ctx, token)
}
