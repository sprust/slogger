package buffer

import (
	"context"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/modules/buffer/entities"
	"slogger_receiver/internal/modules/buffer/errors"
	"slogger_receiver/internal/modules/buffer/internal/domain/actions/mutations/delete_by_ids_action"
	"slogger_receiver/internal/modules/buffer/internal/domain/actions/mutations/mark_failed_action"
	"slogger_receiver/internal/modules/buffer/internal/domain/actions/mutations/move_to_invalid_action"
	"slogger_receiver/internal/modules/buffer/internal/domain/actions/mutations/save_action"
	"slogger_receiver/internal/modules/buffer/internal/domain/actions/queries/find_for_transporter_action"
	"sync"

	"go.mongodb.org/mongo-driver/bson/primitive"
)

var once sync.Once
var instance Service
var _ Service = (*serviceImpl)(nil)

type serviceImpl struct {
	saveAction               *save_action.Action
	findForTransporterAction *find_for_transporter_action.Action
	moveToInvalidAction      *move_to_invalid_action.Action
	deleteByIdsAction        *delete_by_ids_action.Action
	markFailedAction         *mark_failed_action.Action
}

func Get() Service {
	once.Do(func() {
		instance = &serviceImpl{
			saveAction:               save_action.Get(),
			findForTransporterAction: find_for_transporter_action.Get(),
			moveToInvalidAction:      move_to_invalid_action.Get(),
			deleteByIdsAction:        delete_by_ids_action.Get(),
			markFailedAction:         mark_failed_action.Get(),
		}
	})

	return instance
}

func (b *serviceImpl) Save(ctx context.Context, serviceId int, traces *dto.TracesMessage) errors.BufferError {
	return b.saveAction.Handle(ctx, serviceId, traces)
}

func (b *serviceImpl) FindForTransporter(ctx context.Context) (map[int]*dto.ServiceTraces, []entities.InvalidDoc, errors.BufferError) {
	return b.findForTransporterAction.Handle(ctx)
}

func (b *serviceImpl) MoveToInvalid(ctx context.Context, docs []entities.InvalidDoc) errors.BufferError {
	return b.moveToInvalidAction.Handle(ctx, docs)
}

func (b *serviceImpl) DeleteByIds(ctx context.Context, ids []primitive.ObjectID) (int64, errors.BufferError) {
	return b.deleteByIdsAction.Handle(ctx, ids)
}

func (b *serviceImpl) MarkFailed(ctx context.Context, ids []primitive.ObjectID, maxAttempts int) errors.BufferError {
	return b.markFailedAction.Handle(ctx, ids, maxAttempts)
}
