package buffer

import (
	"context"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/modules/buffer/entities"
	"slogger_receiver/internal/modules/buffer/errors"

	"go.mongodb.org/mongo-driver/bson/primitive"
)

type Service interface {
	Save(ctx context.Context, serviceId int, traces *dto.TracesMessage) errors.BufferError
	FindForTransporter(ctx context.Context) (map[int]*dto.ServiceTraces, []entities.InvalidDoc, errors.BufferError)
	MoveToInvalid(ctx context.Context, docs []entities.InvalidDoc) errors.BufferError
	DeleteByIds(ctx context.Context, ids []primitive.ObjectID) (int64, errors.BufferError)
	MarkFailed(ctx context.Context, ids []primitive.ObjectID, maxAttempts int) errors.BufferError
}
