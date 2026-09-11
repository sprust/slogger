package trace_metric_service

import (
	"context"
	"fmt"
	"log/slog"
	"slogger_receiver/internal/repositories/trace_metric_repository"
	"slogger_receiver/pkg/foundation/errs"
	"sync"
	"time"
)

const (
	// SlotSize is the resolution of the dashboard's chart: every count lands in the slot
	// its moment falls into.
	SlotSize = 15 * time.Minute

	// Retention is how long the panel keeps a slot. A trace logged earlier than this is
	// not counted by its logging time: the document would be retired as soon as written.
	Retention = 25 * time.Hour

	flushInterval = 15 * time.Second

	// How long a flush may take once the counts have left memory.
	pushTimeout = 5 * time.Second
)

type pusher interface {
	Push(ctx context.Context, counts map[trace_metric_repository.Key]trace_metric_repository.Counts) error
}

var instance *Service
var once sync.Once

func Get() *Service {
	once.Do(func() {
		instance = newService(trace_metric_repository.Get())
	})

	return instance
}

func newService(repository pusher) *Service {
	return &Service{
		repository: repository,
		counts:     make(map[trace_metric_repository.Key]trace_metric_repository.Counts),
	}
}

// Service counts new traces per service, type and fifteen-minute slot, by the three clocks a
// trace carries: when the source logged it, when the buffer took it in, and when it was
// written to its shard.
type Service struct {
	repository pusher

	countsMu sync.Mutex
	counts   map[trace_metric_repository.Key]trace_metric_repository.Counts
}

// Run writes the counts out every fifteen seconds.
//
// It does not flush on the way out: that is the transporter's job, after its loop has
// stopped feeding this. See Transporter.Run.
func (s *Service) Run(ctx context.Context) {
	flush := time.NewTicker(flushInterval)
	defer flush.Stop()

	for {
		select {
		case <-ctx.Done():
			return
		case <-flush.C:
			if err := s.Flush(ctx); err != nil {
				slog.Error(errs.Err(err).Error())
			}
		}
	}
}

// Flush writes out everything counted since the last one.
//
// The counts leave memory whether or not the write succeeds, as the watchers' buckets do:
// fifteen seconds of a counter lost is a smaller failure than a map growing against a
// Mongo that does not answer.
func (s *Service) Flush(ctx context.Context) error {
	counts := s.takeCounts()

	if len(counts) == 0 {
		return nil
	}

	writeCtx, cancel := context.WithTimeout(context.WithoutCancel(ctx), pushTimeout)
	defer cancel()

	if err := s.repository.Push(writeCtx, counts); err != nil {
		return errs.Err(err)
	}

	return nil
}

// AddTrace counts one new trace.
//
// storedAt is the moment it was written to its shard, and the clock the other two are
// held against: a logging time ahead of it is a client clock running fast and is counted
// as now, and a receiving time that was never read falls back to it.
func (s *Service) AddTrace(serviceId int, traceType string, loggedAt time.Time, receivedAt time.Time, storedAt time.Time) {
	defer func() {
		if recovered := recover(); recovered != nil {
			slog.Error(fmt.Sprintf("trace metric service panicked while counting a trace: %v", recovered))
		}
	}()

	storedAt = storedAt.UTC()

	if loggedAt.After(storedAt) {
		loggedAt = storedAt
	}

	if receivedAt.IsZero() || receivedAt.After(storedAt) {
		receivedAt = storedAt
	}

	s.countsMu.Lock()
	defer s.countsMu.Unlock()

	if storedAt.Sub(loggedAt) < Retention {
		s.add(serviceId, traceType, loggedAt, func(counts *trace_metric_repository.Counts) {
			counts.Logged++
		})
	}

	s.add(serviceId, traceType, receivedAt, func(counts *trace_metric_repository.Counts) {
		counts.Buffered++
	})

	s.add(serviceId, traceType, storedAt, func(counts *trace_metric_repository.Counts) {
		counts.Stored++
	})
}

// add bumps one counter of the slot a moment falls into. The caller holds countsMu.
func (s *Service) add(
	serviceId int,
	traceType string,
	at time.Time,
	bump func(counts *trace_metric_repository.Counts),
) {
	key := trace_metric_repository.Key{
		ServiceId: serviceId,
		Type:      traceType,
		At:        at.UTC().Truncate(SlotSize).UnixMilli(),
	}

	counts := s.counts[key]

	bump(&counts)

	s.counts[key] = counts
}

func (s *Service) takeCounts() map[trace_metric_repository.Key]trace_metric_repository.Counts {
	s.countsMu.Lock()
	defer s.countsMu.Unlock()

	counts := s.counts

	s.counts = make(map[trace_metric_repository.Key]trace_metric_repository.Counts)

	return counts
}
