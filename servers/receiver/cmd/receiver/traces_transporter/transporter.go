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
)

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

		traceIds := map[int][]string{}

		wg := sync.WaitGroup{}

		for serviceId, traces := range serviceTracesMap {
			if traceIds[serviceId] == nil {
				traceIds[serviceId] = make([]string, 0)
			}

			traceIds[serviceId] = append(traceIds[serviceId], traces.GetTraceIds()...)

			wg.Add(1)

			go func() {
				wg.Done()

				count := s.periodicTraceService.Save(ctx, serviceId, traces)

				go s.totalHandledBufferCount.Add(uint64(count))
			}()
		}

		wg.Wait()

		wg = sync.WaitGroup{}

		for serviceId, traceIds := range traceIds {
			wg.Add(1)

			go func() {
				defer wg.Done()

				deletedCount, err := s.bufferService.DeleteByTraceIds(ctx, serviceId, traceIds)

				if err != nil {
					slog.Error(errs.Err(err).Error())

					return
				}

				go s.totalDeletedBufferCount.Add(uint64(deletedCount))
			}()
		}

		wg.Wait()
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

	slog.Warn("Socket server stopped")
}
