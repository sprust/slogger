package flush_action

import (
	"context"
	"slogger_receiver/internal/modules/watcher/errors"
	"slogger_receiver/internal/modules/watcher/internal/domain/services/collector"
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

func (a *Action) Handle(ctx context.Context, all bool) errors.WatcherError {
	if err := a.collector.Flush(ctx, all); err != nil {
		return errors.Internal(err)
	}

	return nil
}
