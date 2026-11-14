package flushTraceMetricAction

import (
	"context"
	"slogger_receiver/internal/modules/trace_metric/internal/domain/services/collector"
	"slogger_receiver/internal/modules/trace_metric/internal/errors"
	"sync"
)

var once sync.Once
var instance *Action

type Action struct {
	collector *collector.Service
}

func Get() *Action {
	once.Do(func() {
		instance = New(
			collector.Get(),
		)
	})

	return instance
}

func New(collector *collector.Service) *Action {
	return &Action{
		collector: collector,
	}
}

func (a *Action) Handle(ctx context.Context) errors.Error {
	if err := a.collector.Flush(ctx); err != nil {
		return errors.Internal(err)
	}

	return nil
}
