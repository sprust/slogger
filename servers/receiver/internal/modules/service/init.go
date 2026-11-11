package service

import (
	"context"
	"slogger_receiver/internal/modules/service/errors"
	"slogger_receiver/internal/modules/service/internal/domain/actions/queries/find_by_token_action"
	"sync"
)

var once sync.Once
var instance Service

var _ Service = (*serviceImpl)(nil)

type serviceImpl struct {
	findServiceIdByTokenAction *find_by_token_action.Action
}

func Get() Service {
	once.Do(func() {
		instance = &serviceImpl{
			findServiceIdByTokenAction: find_by_token_action.Get(),
		}
	})

	return instance
}

func (s *serviceImpl) FindIdByToken(ctx context.Context, token string) (int, errors.ServiceError) {
	return s.findServiceIdByTokenAction.Handle(ctx, token)
}
