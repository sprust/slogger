package trace_metric

import (
	"context"
	"slogger_receiver/internal/modules/trace_metric/errors"
	"slogger_receiver/internal/modules/trace_metric/internal/domain/actions/mutations/add_trace_action"
	"slogger_receiver/internal/modules/trace_metric/internal/domain/actions/mutations/flush_action"
	"slogger_receiver/internal/modules/trace_metric/internal/domain/actions/mutations/run_action"
	"sync"
	"time"
)

var once sync.Once
var instance Service
var _ Service = (*serviceImpl)(nil)

type serviceImpl struct {
	runAction      *run_action.Action
	flushAction    *flush_action.Action
	addTraceAction *add_trace_action.Action
}

func Get() Service {
	once.Do(func() {
		instance = &serviceImpl{
			runAction:      run_action.Get(),
			flushAction:    flush_action.Get(),
			addTraceAction: add_trace_action.Get(),
		}
	})

	return instance
}

func (s *serviceImpl) Run(ctx context.Context) {
	s.runAction.Handle(ctx)
}

func (s *serviceImpl) Flush(ctx context.Context) errors.TraceMetricError {
	return s.flushAction.Handle(ctx)
}

func (s *serviceImpl) AddTrace(
	serviceId int,
	traceType string,
	loggedAt time.Time,
	receivedAt time.Time,
	storedAt time.Time,
) {
	s.addTraceAction.Handle(serviceId, traceType, loggedAt, receivedAt, storedAt)
}
