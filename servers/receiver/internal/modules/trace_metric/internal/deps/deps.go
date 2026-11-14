package deps

import (
	"slogger_receiver/internal/config"
	"slogger_receiver/internal/modules/trace_metric/internal/repository/trace_metric_repository"
	"slogger_receiver/internal/platform/mongodb"
	"sync"
)

var once sync.Once
var instance *Deps

type Deps struct {
	TraceMetricRepository trace_metric_repository.Repository
}

func Get() *Deps {
	once.Do(func() {
		instance = &Deps{
			TraceMetricRepository: trace_metric_repository.NewMongoRepository(
				mongodb.Get(),
				config.Get().Mongodb().CollectionTraceMetrics(),
			),
		}
	})

	return instance
}
