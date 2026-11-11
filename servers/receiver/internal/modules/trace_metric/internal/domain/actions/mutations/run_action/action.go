package run_action

import (
	"context"
	"slogger_receiver/internal/modules/trace_metric/internal/domain/services/collector"
	"sync"
)

var once sync.Once
var instance *Action

type Action struct {
	collector *collector.Service
}

func Get() *Action {
	once.Do(func() {
		instance = &Action{
			collector: collector.Get(),
		}
	})

	return instance
}

func (a *Action) Handle(ctx context.Context) {
	a.collector.Run(ctx)
}
