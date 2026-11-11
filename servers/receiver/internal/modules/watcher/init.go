package watcher

import (
	"context"
	"slogger_receiver/internal/modules/watcher/errors"
	"slogger_receiver/internal/modules/watcher/internal/domain/actions/mutations/add_trace_action"
	"slogger_receiver/internal/modules/watcher/internal/domain/actions/mutations/flush_action"
	"slogger_receiver/internal/modules/watcher/internal/domain/actions/mutations/reload_action"
	"slogger_receiver/internal/modules/watcher/internal/domain/actions/mutations/run_action"
	"sync"
	"time"
)

var once sync.Once
var instance Service
var _ Service = (*serviceImpl)(nil)

type serviceImpl struct {
	runAction      *run_action.Action
	reloadAction   *reload_action.Action
	flushAction    *flush_action.Action
	addTraceAction *add_trace_action.Action
}

func Get() Service {
	once.Do(func() {
		instance = &serviceImpl{
			runAction:      run_action.Get(),
			reloadAction:   reload_action.Get(),
			flushAction:    flush_action.Get(),
			addTraceAction: add_trace_action.Get(),
		}
	})

	return instance
}

func (s *serviceImpl) Run(ctx context.Context) {
	s.runAction.Handle(ctx)
}

func (s *serviceImpl) Reload(ctx context.Context) errors.WatcherError {
	return s.reloadAction.Handle(ctx)
}

func (s *serviceImpl) Flush(ctx context.Context, all bool) errors.WatcherError {
	return s.flushAction.Handle(ctx, all)
}

func (s *serviceImpl) AddTrace(
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
