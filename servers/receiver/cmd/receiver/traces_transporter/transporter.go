package traces_transporter

import (
	"context"
	"log/slog"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/services/buffer_service"
	"slogger_receiver/internal/services/periodic_trace_service"
	"slogger_receiver/internal/services/trace_metric_service"
	"slogger_receiver/internal/services/watcher_service"
	"slogger_receiver/pkg/foundation/errs"
	"strings"
	"sync/atomic"
	"time"

	"go.mongodb.org/mongo-driver/bson/primitive"
)

const maxSaveAttempts = 5

// The pause after a batch that failed because a store was out of reach, doubled on each
// such failure in a row.
const (
	minUnavailablePause = time.Second
	maxUnavailablePause = 30 * time.Second
)

// The pause after a batch that failed for any other reason and spent an attempt of its
// documents, doubled on each such failure in a row: 1, 2, 4, 8 seconds between the five
// attempts. Without it the attempts run back to back, and an error that lasts a second —
// the traces table being recreated by migrate:fresh — moves the batch to the invalid
// buffer before it is over.
const (
	minFailedPause = time.Second
	maxFailedPause = 30 * time.Second
)

// nextPause doubles the pause of the previous failure in a row, within its bounds; the
// first failure waits the lower bound.
func nextPause(previous time.Duration, lower time.Duration, upper time.Duration) time.Duration {
	return min(max(previous*2, lower), upper)
}

// unavailableSigns are the texts of errors that say a store could not take the batch at
// the moment, not that something is wrong with the batch: the network, a timeout, and the
// ClickHouse codes of a server under load — 159 timeout, 202 too many queries, 209/210
// socket and network, 241 memory limit, 242 table read-only, 252 too many parts.
var unavailableSigns = []string{
	"connection refused",
	"connection reset",
	"no such host",
	"i/o timeout",
	"context deadline exceeded",
	"Client.Timeout",
	"server selection error",
	"EOF",
	"Code: 159.",
	"Code: 202.",
	"Code: 209.",
	"Code: 210.",
	"Code: 241.",
	"Code: 242.",
	"Code: 252.",
}

// isUnavailable says whether a failed batch is to wait and be tried again as it is, without
// spending the attempts of its documents.
//
// By the text: errs.Err keeps only the message of what it wraps.
func isUnavailable(err error) bool {
	message := err.Error()

	for _, sign := range unavailableSigns {
		if strings.Contains(message, sign) {
			return true
		}
	}

	return false
}

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
	var unavailablePause time.Duration
	var failedPause time.Duration

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

		result, err := s.periodicTraceService.Save(ctx, serviceTracesMap)

		if err != nil {
			slog.Error("Failed to save traces: " + err.Error())

			// A store out of reach — ClickHouse restarting, over its memory limit, MongoDB
			// failing over — fails every batch alike. Counted as attempts, it would move a
			// whole buffer to the invalid one within seconds of an outage.
			if isUnavailable(err) {
				unavailablePause = nextPause(unavailablePause, minUnavailablePause, maxUnavailablePause)

				s.pause(ctx, unavailablePause)

				continue
			}
		} else {
			unavailablePause = 0
			failedPause = 0

			go s.totalHandledBufferCount.Add(uint64(result.Saved))
		}

		savedIds, failedIds := splitIds(serviceTracesMap, result.Failed, err != nil)

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

		// Only a batch that failed as a whole: the traces that could not be merged spend
		// their own attempts, and the rest of the buffer need not wait for them.
		if err != nil {
			failedPause = nextPause(failedPause, minFailedPause, maxFailedPause)

			s.pause(ctx, failedPause)
		}
	}

	s.flushCounters()

	return nil
}

// flushCounters writes out what the watchers and the trace metrics have collected but not
// yet been given.
//
// Here rather than in a goroutine of its own, and after the loop above rather than
// beside it: this is the only place that both feeds the counters and knows it has
// stopped. Anything watching the context would have to guess when the counting ended.
//
// On a context of its own, because the one this server ran on is already cancelled by
// the time a shutdown reaches here — every write on it would be refused before it was
// sent.
func (s *Transporter) flushCounters() {
	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	if err := watcher_service.Get().Flush(ctx, true); err != nil {
		slog.Error(errs.Err(err).Error())
	}

	if err := trace_metric_service.Get().Flush(ctx); err != nil {
		slog.Error(errs.Err(err).Error())
	}
}

// splitIds sorts the buffer documents of a batch into those whose trace was written and
// those to be tried again: all of them when the batch as a whole failed, otherwise the
// documents of the traces that could not be merged.
func splitIds(
	batch map[int]*dto.ServiceTraces,
	failedTraces map[int]map[string]bool,
	batchFailed bool,
) ([]primitive.ObjectID, []primitive.ObjectID) {
	savedIds := make([]primitive.ObjectID, 0)
	failedIds := make([]primitive.ObjectID, 0)

	for serviceId, traces := range batch {
		for traceId, trace := range traces.Items() {
			if batchFailed || failedTraces[serviceId][traceId] {
				failedIds = append(failedIds, trace.Ids...)
			} else {
				savedIds = append(savedIds, trace.Ids...)
			}
		}
	}

	return savedIds, failedIds
}

func (s *Transporter) GetStats() Stats {
	return Stats{
		Handled: s.totalHandledBufferCount.Load(),
		Deleted: s.totalDeletedBufferCount.Load(),
	}
}

// pause waits the time given or until the transporter is being stopped, whichever comes
// first.
func (s *Transporter) pause(ctx context.Context, duration time.Duration) {
	select {
	case <-ctx.Done():
	case <-time.After(duration):
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
