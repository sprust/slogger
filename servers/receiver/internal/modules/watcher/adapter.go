package watcher

import (
	"context"
	"slogger_receiver/internal/modules/watcher/errors"
	"time"
)

type Service interface {
	Run(ctx context.Context)
	Reload(ctx context.Context) errors.WatcherError
	Flush(ctx context.Context, all bool) errors.WatcherError
	AddTrace(serviceId int, traceId string, traceType string, tags []string, status string, duration *float64, loggedAt time.Time, isNew bool)
}
