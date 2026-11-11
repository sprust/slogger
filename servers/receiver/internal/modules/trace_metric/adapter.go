package trace_metric

import (
	"context"
	"slogger_receiver/internal/modules/trace_metric/errors"
	"time"
)

type Service interface {
	Run(ctx context.Context)
	Flush(ctx context.Context) errors.TraceMetricError
	AddTrace(serviceId int, traceType string, loggedAt time.Time, receivedAt time.Time, storedAt time.Time)
}
