package addTraceAction

import (
	"slogger/internal/modules/watcher/internal/domain/services/collector"
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
