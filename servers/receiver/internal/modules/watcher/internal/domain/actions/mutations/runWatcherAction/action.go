package runWatcherAction

import (
	"context"
	"slogger/internal/modules/watcher/internal/domain/services/collector"
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

func (a *Action) Handle(ctx context.Context) {
	a.collector.Run(ctx)
}
