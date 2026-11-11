package service

import (
	"context"
	"slogger_receiver/internal/modules/service/errors"
)

type Service interface {
	FindIdByToken(ctx context.Context, token string) (int, errors.ServiceError)
}
