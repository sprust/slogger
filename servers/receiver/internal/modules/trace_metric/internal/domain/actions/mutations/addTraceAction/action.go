package addTraceAction

import (
	"slogger/internal/modules/trace_metric/internal/domain/services/collector"
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

func (a *Action) Handle(serviceId int, traceType string, loggedAt time.Time, receivedAt time.Time, storedAt time.Time) {
	a.collector.AddTrace(serviceId, traceType, loggedAt, receivedAt, storedAt)
}
