package add_trace_action

import (
	"slogger_receiver/internal/modules/watcher/internal/domain/services/collector"
	"sync"
	"time"
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

func (a *Action) Handle(
	serviceId int,
	traceId string,
	traceType string,
	tags []string,
	status string,
	duration *float64,
	loggedAt time.Time,
	isNew bool,
) {
	a.collector.AddTrace(serviceId, traceId, traceType, tags, status, duration, loggedAt, isNew)
}
