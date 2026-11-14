package reloadWatcherAction

import (
	"context"
	"slogger/internal/modules/watcher/internal/domain/services/collector"
	"slogger/internal/modules/watcher/internal/errors"
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
	if err := a.collector.Reload(ctx); err != nil {
		return errors.Internal(err)
	}

	return nil
}
