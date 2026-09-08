package traces_transporter

import (
	"context"
	"log/slog"
	"slogger_receiver/internal/services/buffer_service"
	"slogger_receiver/internal/services/periodic_trace_service"
	"slogger_receiver/internal/services/watcher_service"
	"slogger_receiver/pkg/foundation/errs"
	"sync"
	"sync/atomic"
	"time"

	"go.mongodb.org/mongo-driver/bson/primitive"
)

const maxSaveAttempts = 5

type Transporter struct {
	ctx                     context.Context
	cancel                  context.CancelFunc
	bufferService           *buffer_service.Service
	periodicTraceService    *periodic_trace_service.Service
	totalHandledBufferCount atomic.Uint64
	totalDeletedBufferCount atomic.Uint64
	closing                 atomic.Bool
}

type Stats struct {
	Handled uint64
	Deleted uint64
}

func New() *Transporter {
	ctx, cancel := context.WithCancel(context.Background())

	return &Transporter{
		ctx:                  ctx,
		cancel:               cancel,
		bufferService:        buffer_service.Get(),
		periodicTraceService: periodic_trace_service.Get(),
	}
}

func (s *Transporter) Run(ctx context.Context) error {
	slog.Info("Starting traces transporter...")

	go func() {
		select {
		case <-ctx.Done():
			slog.Warn("Shutting down [traces transporter] by context")

			s.stop()
		}
	}()

	// The context as well as the flag: a shutdown cancels it first, and the flush below
	// is the point of getting out of here at all.
	for !s.closing.Load() && ctx.Err() == nil {
		serviceTracesMap, invalidDocs, err := s.bufferService.FindForTransporter(ctx)

		if err != nil {
			slog.Error("Failed to find traces for transporter: " + err.Error())

			time.Sleep(1 * time.Second)

			continue
		}

		// Moved out first, and not through the attempt counter: retrying will not make a
		// document readable, and until it is gone it is among the oldest in the buffer and
		// takes a place in every batch this reads.
		if len(invalidDocs) > 0 {
			if err := s.bufferService.MoveToInvalid(ctx, invalidDocs); err != nil {
				slog.Error(errs.Err(err).Error())
			}
		}

		if len(serviceTracesMap) == 0 {
			time.Sleep(1 * time.Second)

			continue
		}

		var mu sync.Mutex
		savedIds := make([]primitive.ObjectID, 0)
		failedIds := make([]primitive.ObjectID, 0)

		wg := sync.WaitGroup{}

		for serviceId, traces := range serviceTracesMap {
			wg.Add(1)

			go func() {
				defer wg.Done()

				count, failedTraceIds := s.periodicTraceService.Save(ctx, serviceId, traces)

				go s.totalHandledBufferCount.Add(uint64(count))

				failedSet := make(map[string]bool, len(failedTraceIds))

				for _, traceId := range failedTraceIds {
					failedSet[traceId] = true
				}

				localSaved := make([]primitive.ObjectID, 0)
				localFailed := make([]primitive.ObjectID, 0)

				for traceId, trace := range traces.Items() {
					if failedSet[traceId] {
						localFailed = append(localFailed, trace.Ids...)
					} else {
						localSaved = append(localSaved, trace.Ids...)
					}
				}

				mu.Lock()
				savedIds = append(savedIds, localSaved...)
				failedIds = append(failedIds, localFailed...)
				mu.Unlock()
			}()
		}

		wg.Wait()

		if len(savedIds) > 0 {
			deletedCount, err := s.bufferService.DeleteByIds(ctx, savedIds)

			if err != nil {
				slog.Error(errs.Err(err).Error())
			} else {
				go s.totalDeletedBufferCount.Add(uint64(deletedCount))
			}
		}

		if len(failedIds) > 0 {
			if err := s.bufferService.MarkFailed(ctx, failedIds, maxSaveAttempts); err != nil {
				slog.Error(errs.Err(err).Error())
			}
		}
	}

	s.flushWatchers()

	return nil
}

// flushWatchers writes out what the watchers have collected but not yet been given.
//
// Here rather than in a goroutine of its own, and after the loop above rather than
// beside it: this is the only place that both feeds the watchers and knows it has
// stopped. Anything watching the context would have to guess when the counting ended.
//
// On a context of its own, because the one this server ran on is already cancelled by
// the time a shutdown reaches here — every write on it would be refused before it was
// sent.
func (s *Transporter) flushWatchers() {
	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	if err := watcher_service.Get().Flush(ctx, true); err != nil {
		slog.Error(errs.Err(err).Error())
	}
}

func (s *Transporter) GetStats() Stats {
	return Stats{
		Handled: s.totalHandledBufferCount.Load(),
		Deleted: s.totalDeletedBufferCount.Load(),
	}
}
// stop asks the loop above to finish the batch it is on and come out.
//
// The flag stays raised. It used to be lowered again by a `defer` on this function, which
// left it true for the length of one log line — never long enough for a loop whose every
// turn contains a database round trip to see it. Run therefore never returned, the final
// flush of the watcher buckets never happened, and every shutdown ended on the ten-second
// deadline in main.
func (s *Transporter) stop() {
	s.closing.Store(true)

	slog.Warn("Trace transporter stopped")
}
