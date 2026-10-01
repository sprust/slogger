package traces_transporter

import (
	"context"
	"errors"
	"io"
	"log/slog"
	"net"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/repositories/buffer_repository"
	"slogger_receiver/internal/repositories/clickhouse_trace_repository"
	"slogger_receiver/internal/services/buffer_service"
	"slogger_receiver/internal/services/periodic_trace_service"
	"slogger_receiver/internal/services/trace_metric_service"
	"slogger_receiver/internal/services/watcher_service"
	"slogger_receiver/pkg/foundation/errs"
	"strings"
	"sync/atomic"
	"time"

	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
)

const maxSaveAttempts = 5

// batchStopGrace is how long a stop lets the batch being saved run on, short of the 10
// seconds main waits for the transporter. A variable for the tests.
var batchStopGrace = 7 * time.Second

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

// unavailableCodes are the ClickHouse codes of a server that could not take the batch at
// the moment, not of a batch that is wrong: 159 timeout, 202 too many queries, 209/210 socket
// and network, 241 memory limit, 242 table read-only, 252 too many parts — and 60, the
// traces table missing while migrate:fresh or a migration recreates it. A table that never
// comes back keeps the buffer waiting until its 6-hour TTL, rather than moving every trace
// of those hours to the invalid buffer.
var unavailableCodes = map[int]bool{60: true, 159: true, 202: true, 209: true, 210: true, 241: true, 242: true, 252: true}

// unavailableSigns are the texts of network and MongoDB errors that say the same. Never
// matched against a ClickHouse answer: that one may quote the rejected data.
var unavailableSigns = []string{
	"connection refused",
	"connection reset",
	"no such host",
	"i/o timeout",
	"context deadline exceeded",
	"Client.Timeout",
	"server selection error",
	"EOF",
}

// isUnavailable says whether a failed batch is to wait and be tried again as it is, without
// spending the attempts of its documents.
func isUnavailable(err error) bool {
	var queryError *clickhouse_trace_repository.QueryError

	if errors.As(err, &queryError) {
		return unavailableCodes[queryError.Code]
	}

	var netError net.Error

	if errors.As(err, &netError) || errors.Is(err, io.EOF) || errors.Is(err, io.ErrUnexpectedEOF) ||
		errors.Is(err, context.DeadlineExceeded) || mongo.IsNetworkError(err) || mongo.IsTimeout(err) {
		return true
	}

	message := err.Error()

	for _, sign := range unavailableSigns {
		if strings.Contains(message, sign) {
			return true
		}
	}

	return false
}

// buffer is what the transporter needs of the buffer.
type buffer interface {
	FindForTransporter(ctx context.Context) (map[int]*dto.ServiceTraces, []buffer_repository.InvalidDoc, error)
	MoveToInvalid(ctx context.Context, docs []buffer_repository.InvalidDoc) error
	DeleteByIds(ctx context.Context, ids []primitive.ObjectID) (int64, error)
	MarkFailed(ctx context.Context, ids []primitive.ObjectID, maxAttempts int) error
}

// store merges a batch and writes it to ClickHouse.
type store interface {
	Save(ctx context.Context, batch map[int]*dto.ServiceTraces) (periodic_trace_service.Result, error)
}

type Transporter struct {
	ctx                     context.Context
	cancel                  context.CancelFunc
	bufferService           buffer
	periodicTraceService    store
	flush                   func()
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

	transporter := &Transporter{
		ctx:                  ctx,
		cancel:               cancel,
		bufferService:        buffer_service.Get(),
		periodicTraceService: periodic_trace_service.Get(),
	}

	transporter.flush = transporter.flushCounters

	return transporter
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

	var unavailablePause time.Duration
	var failedPause time.Duration

	// A stop lets the batch being saved finish, insert and delete, rather than cutting off an
	// insert ClickHouse may have taken; within a deadline that ends before main stops waiting.
	batchCtx, cancelBatch := context.WithCancel(context.WithoutCancel(ctx))
	defer cancelBatch()

	grace := batchStopGrace

	stopBatchLater := context.AfterFunc(ctx, func() {
		time.AfterFunc(grace, cancelBatch)
	})
	defer stopBatchLater()

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

		result, err := s.periodicTraceService.Save(batchCtx, serviceTracesMap)

		if err != nil {
			slog.Error("Failed to save traces: " + err.Error())

			// A store out of reach — ClickHouse restarting, over its memory limit, MongoDB
			// failing over — fails every batch alike. Counted as attempts, it would move a
			// whole buffer to the invalid one within seconds of an outage.
			// A save cut off by the stop's deadline is not the batch's fault either.
			if ctx.Err() != nil {
				continue
			}

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
			deletedCount, err := s.bufferService.DeleteByIds(batchCtx, savedIds)

			if err != nil {
				slog.Error(errs.Err(err).Error())
			} else {
				go s.totalDeletedBufferCount.Add(uint64(deletedCount))
			}
		}

		if len(failedIds) > 0 {
			if err := s.bufferService.MarkFailed(batchCtx, failedIds, maxSaveAttempts); err != nil {
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

	s.flush()

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
