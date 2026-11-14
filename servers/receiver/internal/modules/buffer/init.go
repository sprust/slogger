package buffer

import (
	"context"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/modules/buffer/entities"
	"slogger_receiver/internal/modules/buffer/internal/domain/actions/mutations/deleteBufferByIdsAction"
	"slogger_receiver/internal/modules/buffer/internal/domain/actions/mutations/markBufferFailedAction"
	"slogger_receiver/internal/modules/buffer/internal/domain/actions/mutations/moveBufferToInvalidAction"
	"slogger_receiver/internal/modules/buffer/internal/domain/actions/mutations/saveBufferAction"
	"slogger_receiver/internal/modules/buffer/internal/domain/actions/queries/findBufferForTransporterAction"
	"slogger_receiver/internal/modules/buffer/internal/errors"
	"sync"

	"go.mongodb.org/mongo-driver/bson/primitive"
)

var once sync.Once
var instance *Buffer

type Buffer struct {
	saveAction               *saveBufferAction.Action
	findForTransporterAction *findBufferForTransporterAction.Action
	moveToInvalidAction      *moveBufferToInvalidAction.Action
	deleteByIdsAction        *deleteBufferByIdsAction.Action
	markFailedAction         *markBufferFailedAction.Action
}

func Get() *Buffer {
	once.Do(func() {
		instance = &Buffer{
			saveAction:               saveBufferAction.Get(),
			findForTransporterAction: findBufferForTransporterAction.Get(),
			moveToInvalidAction:      moveBufferToInvalidAction.Get(),
			deleteByIdsAction:        deleteBufferByIdsAction.Get(),
			markFailedAction:         markBufferFailedAction.Get(),
		}
	})

	return instance
}

func (b *Buffer) Save(ctx context.Context, serviceId int, traces *dto.TracesMessage) errors.Error {
	return b.saveAction.Handle(ctx, serviceId, traces)
}

func (b *Buffer) FindForTransporter(ctx context.Context) (map[int]*dto.ServiceTraces, []entities.InvalidDoc, errors.Error) {
	return b.findForTransporterAction.Handle(ctx)
}

func (b *Buffer) MoveToInvalid(ctx context.Context, docs []entities.InvalidDoc) errors.Error {
	return b.moveToInvalidAction.Handle(ctx, docs)
}

func (b *Buffer) DeleteByIds(ctx context.Context, ids []primitive.ObjectID) (int64, errors.Error) {
	return b.deleteByIdsAction.Handle(ctx, ids)
}

func (b *Buffer) MarkFailed(ctx context.Context, ids []primitive.ObjectID, maxAttempts int) errors.Error {
	return b.markFailedAction.Handle(ctx, ids, maxAttempts)
}
