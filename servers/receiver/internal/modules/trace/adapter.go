package trace

import (
	"context"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/modules/trace/entities"
	"slogger_receiver/internal/modules/trace/errors"
)

type Service interface {
	Save(ctx context.Context, batch map[int]*dto.ServiceTraces) (entities.Result, errors.TraceError)
	Insert(ctx context.Context, rows []entities.Row) errors.TraceError
}
