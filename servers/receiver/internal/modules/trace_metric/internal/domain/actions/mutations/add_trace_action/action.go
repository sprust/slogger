package add_trace_action

import (
	"slogger_receiver/internal/modules/trace_metric/internal/domain/services/collector"
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

func (a *Action) Handle(serviceId int, traceType string, loggedAt time.Time, receivedAt time.Time, storedAt time.Time) {
	a.collector.AddTrace(serviceId, traceType, loggedAt, receivedAt, storedAt)
}
