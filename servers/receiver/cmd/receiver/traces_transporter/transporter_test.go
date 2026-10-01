package traces_transporter

import (
	"context"
	"errors"
	"io"
	"slices"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/repositories/buffer_repository"
	"slogger_receiver/internal/repositories/clickhouse_trace_repository"
	"slogger_receiver/internal/services/periodic_trace_service"
	"slogger_receiver/pkg/foundation/errs"
	"sync"
	"testing"
	"time"

	"go.mongodb.org/mongo-driver/bson/primitive"
)

// The flag is the only thing that gets the loop in Run to come out, and coming out is
// what writes the watchers' last buckets and lets main stop without waiting out its
// deadline.
//
// It used to be lowered again by a `defer` on stop itself, which left it raised for the
// length of one log line — never long enough for a loop whose every turn contains a
// database round trip to see it.
func TestStopLeavesTheClosingFlagRaised(t *testing.T) {
	transporter := &Transporter{}

	transporter.stop()

	if !transporter.closing.Load() {
		t.Fatal("stop() left the transporter looking like it was still running")
	}
}

func TestSplitIdsSendsFailedTracesBack(t *testing.T) {
	batch := map[int]*dto.ServiceTraces{1: {}}
	batch[1].AddId("saved", primitive.ObjectID{1})
	batch[1].AddId("failed", primitive.ObjectID{2})
	batch[1].AddId("failed", primitive.ObjectID{3})

	saved, failed := splitIds(batch, map[int]map[string]bool{1: {"failed": true}}, false)

	if len(saved) != 1 || saved[0] != (primitive.ObjectID{1}) || len(failed) != 2 {
		t.Fatalf("unexpected split %v / %v", saved, failed)
	}
}

func TestSplitIdsSendsTheWholeBatchBackWhenItFailed(t *testing.T) {
	batch := map[int]*dto.ServiceTraces{1: {}}
	batch[1].AddId("a", primitive.ObjectID{1})
	batch[1].AddId("b", primitive.ObjectID{2})

	saved, failed := splitIds(batch, nil, true)

	if len(saved) != 0 || len(failed) != 2 {
		t.Fatalf("unexpected split %v / %v", saved, failed)
	}
}

func TestAStoreOutOfReachIsNotTheBatchsFault(t *testing.T) {
	unavailable := []error{
		errors.New(`Post "http://clickhouse:8123/?database=slogger": dial tcp 172.18.0.5:8123: connect: connection refused`),
		&clickhouse_trace_repository.QueryError{Code: 241, Message: "Code: 241. DB::Exception: (total) memory limit exceeded: would use 4.01 GiB"},
		&clickhouse_trace_repository.QueryError{Code: 252, Message: "Code: 252. DB::Exception: Too many parts (3001) in table"},
		&clickhouse_trace_repository.QueryError{Code: 60, Message: "Code: 60. DB::Exception: Unknown table expression identifier 'traces' in scope SELECT"},
		errors.New("server selection error: context deadline exceeded"),
		io.ErrUnexpectedEOF,
		context.DeadlineExceeded,
	}

	for _, err := range unavailable {
		if !isUnavailable(errs.Err(errs.Err(err))) {
			t.Fatalf("taken for a fault of the batch: %v", err)
		}
	}

	// The answer quotes the rejected data, and the data may say anything.
	faults := []error{
		&clickhouse_trace_repository.QueryError{Code: 27, Message: `Code: 27. DB::Exception: Cannot parse input: expected '"' before: 'connection refused, unexpected EOF, Code: 241.'`},
		&clickhouse_trace_repository.QueryError{Code: 117, Message: `Code: 117. DB::Exception: Cannot parse JSON object here: {"error":"i/o timeout"}`},
		&clickhouse_trace_repository.QueryError{Code: 53, Message: "Code: 53. DB::Exception: Type mismatch"},
	}

	for _, err := range faults {
		if isUnavailable(errs.Err(errs.Err(err))) {
			t.Fatalf("taken for an outage: %v", err)
		}
	}
}

func TestFailedBatchesWaitLongerEachTime(t *testing.T) {
	var pause time.Duration

	got := make([]time.Duration, 0)

	for range 7 {
		pause = nextPause(pause, minFailedPause, maxFailedPause)

		got = append(got, pause)
	}

	want := []time.Duration{
		time.Second, 2 * time.Second, 4 * time.Second, 8 * time.Second,
		16 * time.Second, 30 * time.Second, 30 * time.Second,
	}

	if !slices.Equal(got, want) {
		t.Fatalf("pauses %v, want %v", got, want)
	}
}

// fakeBuffer hands out its documents in batches and records what happens to them.
type fakeBuffer struct {
	mutex   sync.Mutex
	ids     []primitive.ObjectID
	limit   int
	deleted map[primitive.ObjectID]int
	marked  map[primitive.ObjectID]int
}

func (b *fakeBuffer) FindForTransporter(context.Context) (map[int]*dto.ServiceTraces, []buffer_repository.InvalidDoc, error) {
	b.mutex.Lock()
	defer b.mutex.Unlock()

	batch := map[int]*dto.ServiceTraces{1: {}}

	for _, id := range b.ids[:min(b.limit, len(b.ids))] {
		batch[1].AddCreating(&dto.TraceCreating{TraceId: id.Hex()})
		batch[1].AddId(id.Hex(), id)
	}

	return batch, nil, nil
}

func (b *fakeBuffer) MoveToInvalid(context.Context, []buffer_repository.InvalidDoc) error {
	return nil
}

func (b *fakeBuffer) DeleteByIds(ctx context.Context, ids []primitive.ObjectID) (int64, error) {
	if ctx.Err() != nil {
		return 0, ctx.Err()
	}

	b.mutex.Lock()
	defer b.mutex.Unlock()

	for _, id := range ids {
		b.deleted[id]++
		b.ids = slices.DeleteFunc(b.ids, func(kept primitive.ObjectID) bool { return kept == id })
	}

	return int64(len(ids)), nil
}

func (b *fakeBuffer) MarkFailed(_ context.Context, ids []primitive.ObjectID, _ int) error {
	b.mutex.Lock()
	defer b.mutex.Unlock()

	for _, id := range ids {
		b.marked[id]++
	}

	return nil
}

// fakeStore stops the run in the middle of its first save and fails the way an HTTP
// request does when its context is cancelled halfway.
type fakeStore struct {
	cancel context.CancelFunc
	saved  []primitive.ObjectID
}

func (s *fakeStore) Save(ctx context.Context, batch map[int]*dto.ServiceTraces) (periodic_trace_service.Result, error) {
	s.cancel()

	if ctx.Err() != nil {
		return periodic_trace_service.Result{}, ctx.Err()
	}

	for _, traces := range batch {
		for _, trace := range traces.Items() {
			s.saved = append(s.saved, trace.Ids...)
		}
	}

	return periodic_trace_service.Result{Saved: len(s.saved)}, nil
}

// A stop in the middle of a save lets that batch finish and leave the buffer: cut off, its
// insert could have reached ClickHouse and be saved and counted again after the restart.
func TestAStopLetsTheBatchBeingSavedFinish(t *testing.T) {
	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	buffer := &fakeBuffer{limit: 5, deleted: map[primitive.ObjectID]int{}, marked: map[primitive.ObjectID]int{}}

	for index := range 8 {
		buffer.ids = append(buffer.ids, primitive.ObjectID{byte(index + 1)})
	}

	store := &fakeStore{cancel: cancel}
	flushed := false

	transporter := &Transporter{bufferService: buffer, periodicTraceService: store}
	transporter.flush = func() { flushed = true }

	if err := transporter.Run(ctx); err != nil {
		t.Fatal(err)
	}

	if len(store.saved) != 5 || len(buffer.marked) != 0 || !flushed {
		t.Fatalf("saved %d, marked %d, flushed %v", len(store.saved), len(buffer.marked), flushed)
	}

	for _, id := range store.saved {
		if buffer.deleted[id] != 1 {
			t.Fatalf("%s was saved but not deleted", id.Hex())
		}
	}

	if len(buffer.ids) != 3 {
		t.Fatalf("left %d in the buffer, want the 3 never read", len(buffer.ids))
	}
}

// hangingStore never answers until its context is cancelled.
type hangingStore struct {
	cancel context.CancelFunc
}

func (s *hangingStore) Save(ctx context.Context, _ map[int]*dto.ServiceTraces) (periodic_trace_service.Result, error) {
	s.cancel()

	<-ctx.Done()

	return periodic_trace_service.Result{}, ctx.Err()
}

// A save that does not finish within the stop's deadline is cut off, and its batch stays in
// the buffer without an attempt spent.
func TestAStopCutsOffASaveThatHangs(t *testing.T) {
	grace := batchStopGrace
	batchStopGrace = 50 * time.Millisecond

	defer func() { batchStopGrace = grace }()

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	buffer := &fakeBuffer{limit: 5, deleted: map[primitive.ObjectID]int{}, marked: map[primitive.ObjectID]int{}}

	for index := range 5 {
		buffer.ids = append(buffer.ids, primitive.ObjectID{byte(index + 1)})
	}

	transporter := &Transporter{bufferService: buffer, periodicTraceService: &hangingStore{cancel: cancel}}
	transporter.flush = func() {}

	started := time.Now()

	if err := transporter.Run(ctx); err != nil {
		t.Fatal(err)
	}

	if elapsed := time.Since(started); elapsed > 2*time.Second {
		t.Fatalf("the stop waited %s", elapsed)
	}

	if len(buffer.marked) != 0 || len(buffer.deleted) != 0 || len(buffer.ids) != 5 {
		t.Fatalf("marked %d, deleted %d, left %d", len(buffer.marked), len(buffer.deleted), len(buffer.ids))
	}
}
