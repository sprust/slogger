package traces_transporter

import (
	"context"
	"log/slog"
	"slogger_receiver/internal/services/buffer_service"
	"slogger_receiver/internal/services/periodic_trace_service"
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

	for !s.closing.Load() {
		serviceTracesMap, err := s.bufferService.FindForTransporter(ctx)

		if err != nil {
			slog.Error("Failed to find traces for transporter: " + err.Error())

			time.Sleep(1 * time.Second)

			continue
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

	for s.closing.Load() {
		time.Sleep(1 * time.Second)
	}

	s.closing.Store(false)

	return nil
}

func (s *Transporter) GetStats() Stats {
	return Stats{
		Handled: s.totalHandledBufferCount.Load(),
		Deleted: s.totalDeletedBufferCount.Load(),
	}
}
func (s *Transporter) stop() {
	s.closing.Store(true)
	defer s.closing.Store(false)

	slog.Warn("Trace transporter stopped")
}
