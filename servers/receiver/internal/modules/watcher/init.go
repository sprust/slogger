package watcher

import (
	"context"
	"slogger/internal/modules/watcher/internal/domain/actions/mutations/addTraceAction"
	"slogger/internal/modules/watcher/internal/domain/actions/mutations/flushWatcherAction"
	"slogger/internal/modules/watcher/internal/domain/actions/mutations/reloadWatcherAction"
	"slogger/internal/modules/watcher/internal/domain/actions/mutations/runWatcherAction"
	"slogger/internal/modules/watcher/internal/errors"
	"sync"
	"time"
)

var once sync.Once
var instance *Watcher

type Watcher struct {
	runAction      *runWatcherAction.Action
	reloadAction   *reloadWatcherAction.Action
	flushAction    *flushWatcherAction.Action
	addTraceAction *addTraceAction.Action
}

func Get() *Watcher {
	once.Do(func() {
		instance = &Watcher{
			runAction:      runWatcherAction.Get(),
			reloadAction:   reloadWatcherAction.Get(),
			flushAction:    flushWatcherAction.Get(),
			addTraceAction: addTraceAction.Get(),
		}
	})

	return instance
}

func (s *Watcher) Run(ctx context.Context) {
	s.runAction.Handle(ctx)
}

func (s *Watcher) Reload(ctx context.Context) errors.Error {
	return s.reloadAction.Handle(ctx)
}

func (s *Watcher) Flush(ctx context.Context, all bool) errors.Error {
	return s.flushAction.Handle(ctx, all)
}

func (s *Watcher) AddTrace(
	serviceId int,
	traceId string,
	traceType string,
	tags []string,
	status string,
	duration *float64,
	loggedAt time.Time,
	isNew bool,
) {
	s.addTraceAction.Handle(serviceId, traceId, traceType, tags, status, duration, loggedAt, isNew)
}
