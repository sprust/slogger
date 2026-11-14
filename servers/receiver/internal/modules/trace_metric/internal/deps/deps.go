package deps

import (
	"slogger/internal/config"
	"slogger/internal/modules/trace_metric/internal/repository/trace_metric_repository"
	"slogger/internal/platform/mongodb"
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
