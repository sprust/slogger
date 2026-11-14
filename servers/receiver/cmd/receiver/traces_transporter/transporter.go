package traces_transporter

import (
	"context"
	"log/slog"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/modules/buffer"
	bufferEntities "slogger_receiver/internal/modules/buffer/entities"
	"slogger_receiver/internal/modules/trace"
	"slogger_receiver/internal/modules/trace/entities"
	"slogger_receiver/internal/modules/trace_metric"
	"slogger_receiver/internal/modules/watcher"
	"slogger_receiver/pkg/foundation/errs"
	"sync/atomic"
	"time"

	"go.mongodb.org/mongo-driver/bson/primitive"
)

const maxSaveAttempts = 5

// batchStopGrace is how long a stop lets the batch being saved run on, short of the 10
// seconds main waits for the transporter. A variable for the tests.
var batchStopGrace = 7 * time.Second

// Backoff gives a transient failure time to clear before the batch exhausts its attempts.
const (
	minFailedPause = time.Second
	maxFailedPause = 30 * time.Second
)

// nextPause doubles the pause of the previous failure in a row, within its bounds; the
// first failure waits the lower bound.
func nextPause(previous time.Duration, lower time.Duration, upper time.Duration) time.Duration {
	return min(max(previous*2, lower), upper)
}

type Transporter struct {
	ctx                     context.Context
	cancel                  context.CancelFunc
	findBuffer              func(context.Context) (map[int]*dto.ServiceTraces, []bufferEntities.InvalidDoc, error)
	moveBufferToInvalid     func(context.Context, []bufferEntities.InvalidDoc) error
	deleteBufferByIds       func(context.Context, []primitive.ObjectID) (int64, error)
	markBufferFailed        func(context.Context, []primitive.ObjectID, int) error
	saveTraces              func(context.Context, map[int]*dto.ServiceTraces) (entities.Result, error)
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
		ctx:    ctx,
		cancel: cancel,
		findBuffer: func(ctx context.Context) (map[int]*dto.ServiceTraces, []bufferEntities.InvalidDoc, error) {
			return buffer.Get().FindForTransporter(ctx)
		},
		moveBufferToInvalid: func(ctx context.Context, docs []bufferEntities.InvalidDoc) error {
			return buffer.Get().MoveToInvalid(ctx, docs)
		},
		deleteBufferByIds: func(ctx context.Context, ids []primitive.ObjectID) (int64, error) {
			return buffer.Get().DeleteByIds(ctx, ids)
		},
		markBufferFailed: func(ctx context.Context, ids []primitive.ObjectID, maxAttempts int) error {
			return buffer.Get().MarkFailed(ctx, ids, maxAttempts)
		},
		saveTraces: func(ctx context.Context, batch map[int]*dto.ServiceTraces) (entities.Result, error) {
			return trace.Get().Save(ctx, batch)
		},
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

	var failedPause time.Duration

	// Allow the current batch to finish saving and leave the buffer before shutdown.
	// The deadline must expire before main stops waiting.
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
		serviceTracesMap, invalidDocs, bufferErr := s.findBuffer(ctx)

		if bufferErr != nil {
			slog.Error("Failed to find traces for transporter: " + bufferErr.Error())

			time.Sleep(1 * time.Second)

			continue
		}

		// Moved out first, and not through the attempt counter: retrying will not make a
		// document readable, and until it is gone it is among the oldest in the buffer and
		// takes a place in every batch this reads.
		if len(invalidDocs) > 0 {
			if err := s.moveBufferToInvalid(ctx, invalidDocs); err != nil {
				slog.Error(errs.Err(err).Error())
			}
		}

		if len(serviceTracesMap) == 0 {
			time.Sleep(1 * time.Second)

			continue
		}

		result, err := s.saveTraces(batchCtx, serviceTracesMap)

		if err != nil {
			slog.Error("Failed to save traces: " + err.Error())

			// A save cut off by shutdown does not spend the batch's attempts.
			if ctx.Err() != nil {
				continue
			}
		} else {
			failedPause = 0

			go s.totalHandledBufferCount.Add(uint64(result.Saved))
		}

		savedIds, failedIds := splitIds(serviceTracesMap, result.Failed, err != nil)

		if len(savedIds) > 0 {
			deletedCount, err := s.deleteBufferByIds(batchCtx, savedIds)

			if err != nil {
				slog.Error(errs.Err(err).Error())
			} else {
				go s.totalDeletedBufferCount.Add(uint64(deletedCount))
			}
		}

		if len(failedIds) > 0 {
			if err := s.markBufferFailed(batchCtx, failedIds, maxSaveAttempts); err != nil {
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

	if err := watcher.Get().Flush(ctx, true); err != nil {
		slog.Error(errs.Err(err).Error())
	}

	if err := trace_metric.Get().Flush(ctx); err != nil {
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
