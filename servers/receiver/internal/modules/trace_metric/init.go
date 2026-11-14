package trace_metric

import (
	"context"
	"slogger/internal/modules/trace_metric/internal/domain/actions/mutations/addTraceAction"
	"slogger/internal/modules/trace_metric/internal/domain/actions/mutations/flushTraceMetricAction"
	"slogger/internal/modules/trace_metric/internal/domain/actions/mutations/runTraceMetricAction"
	"slogger/internal/modules/trace_metric/internal/errors"
	"sync"
	"time"
)

var once sync.Once
var instance *TraceMetric

type TraceMetric struct {
	runAction      *runTraceMetricAction.Action
	flushAction    *flushTraceMetricAction.Action
	addTraceAction *addTraceAction.Action
}

func Get() *TraceMetric {
	once.Do(func() {
		instance = &TraceMetric{
			runAction:      runTraceMetricAction.Get(),
			flushAction:    flushTraceMetricAction.Get(),
			addTraceAction: addTraceAction.Get(),
		}
	})

	return instance
}

func (s *TraceMetric) Run(ctx context.Context) {
	s.runAction.Handle(ctx)
}

func (s *TraceMetric) Flush(ctx context.Context) errors.Error {
	return s.flushAction.Handle(ctx)
}

func (s *TraceMetric) AddTrace(
	serviceId int,
	traceType string,
	loggedAt time.Time,
	receivedAt time.Time,
	storedAt time.Time,
) {
	s.addTraceAction.Handle(serviceId, traceType, loggedAt, receivedAt, storedAt)
}
