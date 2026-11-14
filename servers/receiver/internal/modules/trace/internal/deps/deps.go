package deps

import (
	"slogger/internal/modules/trace/internal/repository/pending_trace_repository"
	"slogger/internal/modules/trace/internal/repository/trace_repository"
	"slogger/internal/platform/clickhouse"
	"slogger/internal/platform/mongodb"
	"sync"
)

var once sync.Once
var instance *Deps

type Deps struct {
	TraceRepository        trace_repository.Repository
	PendingTraceRepository pending_trace_repository.Repository
}

func Get() *Deps {
	once.Do(func() {
		instance = &Deps{
			TraceRepository: trace_repository.NewClickhouseRepository(
				clickhouse.Get(),
			),
			PendingTraceRepository: pending_trace_repository.NewMongoRepository(
				mongodb.Get(),
			),
		}
	})

	return instance
}
