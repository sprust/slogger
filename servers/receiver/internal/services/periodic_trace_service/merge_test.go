package periodic_trace_service

import (
	"context"
	"encoding/json"
	"errors"
	"reflect"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/repositories/clickhouse_trace_repository"
	"slogger_receiver/internal/repositories/pending_trace_repository"
	"testing"
	"time"
)

var (
	loggedAt = time.Date(2026, 9, 29, 10, 0, 0, 123456000, time.UTC)
	firstAt  = time.Date(2026, 9, 29, 10, 0, 1, 0, time.UTC)
	secondAt = time.Date(2026, 9, 29, 10, 0, 5, 0, time.UTC)
)

func float(value float64) *float64 {
	return &value
}

func data(t *testing.T, raw string) dto.Data {
	t.Helper()

	var value dto.Data

	if err := json.Unmarshal([]byte(raw), &value); err != nil {
		t.Fatal(err)
	}

	return value
}

func creating(t *testing.T) *dto.TraceCreating {
	parent := "parent-1"

	return &dto.TraceCreating{
		TraceId:       "trace-1",
		ParentTraceId: &parent,
		Type:          "request",
		Status:        "started",
		Tags:          []interface{}{"api", 5, "v2"},
		Data:          data(t, `{"path":"/x","method":"GET"}`),
		Memory:        float(40),
		LoggedAt:      "2026-09-29 10:00:00.123456",
	}
}

func updating(t *testing.T) *dto.TraceUpdating {
	return &dto.TraceUpdating{
		TraceId:        "trace-1",
		Status:         "success",
		Data:           data(t, `{"code":200}`),
		Duration:       float(0.2),
		ParentLoggedAt: "2026-09-29 10:00:00.123456",
	}
}

// storedFrom reads a written row back the way the repository hands a stored trace over.
func storedFrom(row clickhouse_trace_repository.Row) *clickhouse_trace_repository.StoredTrace {
	createdAt, _ := time.Parse(clickhouse_trace_repository.TimeLayout, row.CreatedAt)

	return &clickhouse_trace_repository.StoredTrace{
		ParentTraceId: row.ParentTraceId,
		Type:          row.Type,
		Status:        row.Status,
		Tags:          row.Tags,
		RawData:       row.RawData,
		Duration:      row.Duration,
		Memory:        row.Memory,
		Cpu:           row.Cpu,
		Pid:           row.Pid,
		CreatedAt:     createdAt,
	}
}

func TestCreateAndUpdateInOneBatch(t *testing.T) {
	merged := mergeTrace(1, "trace-1", &dto.Traces{Creating: creating(t), Updating: updating(t)}, nil, loggedAt, firstAt)

	row := merged.row

	if row.Type != "request" || row.Status != "success" || row.ParentTraceId != "parent-1" {
		t.Fatalf("unexpected row %+v", row)
	}

	if *row.Duration != 0.2 || *row.Memory != 40 || row.Cpu != nil {
		t.Fatalf("unexpected metrics %+v", row)
	}

	if row.RawData != `{"code":200}` || string(row.Data) != `{"code":200}` {
		t.Fatalf("the update's data did not win: %s", row.RawData)
	}

	if !reflect.DeepEqual(row.Tags, []string{"api", "v2"}) {
		t.Fatalf("unexpected tags %v", row.Tags)
	}

	if row.LoggedAt != "2026-09-29 10:00:00.123456" || row.CreatedAt != row.UpdatedAt {
		t.Fatalf("unexpected times %+v", row)
	}

	if !merged.countsAsNew || merged.newDuration == nil {
		t.Fatal("a complete trace was not reported as new with its duration")
	}
}

func TestUpdateBeforeCreate(t *testing.T) {
	first := mergeTrace(1, "trace-1", &dto.Traces{Updating: updating(t)}, nil, loggedAt, firstAt)

	if first.row.Type != unknownTraceType || first.countsAsNew || first.newDuration != nil {
		t.Fatalf("an update alone was counted: %+v", first)
	}

	second := mergeTrace(1, "trace-1", &dto.Traces{Creating: creating(t)}, storedFrom(first.row), loggedAt, secondAt)

	row := second.row

	if row.Type != "request" || row.Status != "success" || *row.Duration != 0.2 {
		t.Fatalf("the late create undid the update: %+v", row)
	}

	if row.RawData != `{"code":200}` {
		t.Fatalf("the stored data lost to the create: %s", row.RawData)
	}

	if row.ParentTraceId != "parent-1" || !reflect.DeepEqual(row.Tags, []string{"api", "v2"}) {
		t.Fatalf("the create did not fill in what the update lacked: %+v", row)
	}

	if row.CreatedAt != first.row.CreatedAt || row.UpdatedAt == first.row.UpdatedAt {
		t.Fatalf("created at is not kept or updated at is not moved: %+v", row)
	}

	if !second.countsAsNew || second.newDuration == nil || *second.newDuration != 0.2 {
		t.Fatal("the create that gave the trace its type was not counted with the stored duration")
	}
}

func TestRepeatedCreateChangesNothing(t *testing.T) {
	first := mergeTrace(1, "trace-1", &dto.Traces{Creating: creating(t), Updating: updating(t)}, nil, loggedAt, firstAt)

	second := mergeTrace(1, "trace-1", &dto.Traces{Creating: creating(t)}, storedFrom(first.row), loggedAt, secondAt)

	if second.row.Status != "success" || *second.row.Duration != 0.2 || second.row.RawData != `{"code":200}` {
		t.Fatalf("a repeated create overwrote the trace: %+v", second.row)
	}

	if second.countsAsNew || second.newDuration != nil {
		t.Fatal("a repeated create was counted again")
	}
}

func TestCreateWithoutUpdate(t *testing.T) {
	merged := mergeTrace(1, "trace-1", &dto.Traces{Creating: creating(t)}, nil, loggedAt, firstAt)

	if merged.row.Status != "started" || merged.row.Duration != nil {
		t.Fatalf("unexpected row %+v", merged.row)
	}

	if merged.row.RawData != `{"path":"/x","method":"GET"}` {
		t.Fatalf("the data lost its order: %s", merged.row.RawData)
	}
}

func TestDataThatIsNotAnObjectStaysOutOfTheJsonColumn(t *testing.T) {
	trace := creating(t)
	trace.Data = data(t, `[1,2]`)

	merged := mergeTrace(1, "trace-1", &dto.Traces{Creating: trace}, nil, loggedAt, firstAt)

	if string(merged.row.Data) != `{}` || merged.row.RawData != `[1,2]` {
		t.Fatalf("unexpected data %s / %s", merged.row.Data, merged.row.RawData)
	}
}

type fakeStore struct {
	stored    map[clickhouse_trace_repository.Key]clickhouse_trace_repository.StoredTrace
	keys      []clickhouse_trace_repository.Key
	inserted  []clickhouse_trace_repository.Row
	findErr   error
	insertErr error
}

func (s *fakeStore) FindExisting(_ context.Context, keys []clickhouse_trace_repository.Key) (map[clickhouse_trace_repository.Key]clickhouse_trace_repository.StoredTrace, error) {
	s.keys = keys

	return s.stored, s.findErr
}

func (s *fakeStore) Insert(_ context.Context, rows []clickhouse_trace_repository.Row) error {
	s.inserted = rows

	return s.insertErr
}

type fakePending struct {
	traces   map[string]pending_trace_repository.PendingTrace
	saved    []pending_trace_repository.PendingTrace
	forgot   []string
	findErr  error
	applyErr error
}

func (p *fakePending) FindMany(_ context.Context, ids []string) (map[string]pending_trace_repository.PendingTrace, error) {
	result := map[string]pending_trace_repository.PendingTrace{}

	for _, id := range ids {
		if trace, found := p.traces[id]; found {
			result[id] = trace
		}
	}

	return result, p.findErr
}

func (p *fakePending) Apply(_ context.Context, save []pending_trace_repository.PendingTrace, forget []string, _ time.Time) error {
	p.saved = save
	p.forgot = forget

	return p.applyErr
}

func batch(serviceId int, creating *dto.TraceCreating, updating *dto.TraceUpdating) map[int]*dto.ServiceTraces {
	traces := &dto.ServiceTraces{}

	if creating != nil {
		traces.AddCreating(creating)
	}

	if updating != nil {
		traces.AddUpdating(updating)
	}

	return map[int]*dto.ServiceTraces{serviceId: traces}
}

func TestAFinalTraceIsInsertedWithoutAnyRead(t *testing.T) {
	store, pending := &fakeStore{}, &fakePending{}

	result, err := New(store, pending).Save(context.Background(), batch(1, creating(t), updating(t)))

	if err != nil {
		t.Fatal(err)
	}

	if result.Saved != 1 || len(store.inserted) != 1 || len(store.keys) != 0 {
		t.Fatalf("expected one insert and no reads, got %+v, %d rows, %d keys", result, len(store.inserted), len(store.keys))
	}

	if store.inserted[0].Status != "success" || !isDoneMark(pending.saved) || len(pending.forgot) != 0 {
		t.Fatalf("unexpected write %+v, pending %+v / %v", store.inserted[0], pending.saved, pending.forgot)
	}
}

// isDoneMark says whether what was kept is the done mark of trace-1 and nothing else.
func isDoneMark(saved []pending_trace_repository.PendingTrace) bool {
	return len(saved) == 1 && saved[0].Done && saved[0].TraceId == "trace-1" && saved[0].RawData == ""
}

func TestAStartedCreateIsInsertedAndKeptPending(t *testing.T) {
	store, pending := &fakeStore{}, &fakePending{}

	if _, err := New(store, pending).Save(context.Background(), batch(1, creating(t), nil)); err != nil {
		t.Fatal(err)
	}

	if len(store.inserted) != 1 || store.inserted[0].Status != "started" {
		t.Fatalf("the create was not inserted: %+v", store.inserted)
	}

	if len(pending.saved) != 1 || pending.saved[0].HasUpdate || pending.saved[0].Type != "request" {
		t.Fatalf("the create was not kept pending: %+v", pending.saved)
	}
}

func TestTheUpdateCompletesAPendingCreate(t *testing.T) {
	store, pending := &fakeStore{}, &fakePending{}

	if _, err := New(store, pending).Save(context.Background(), batch(1, creating(t), nil)); err != nil {
		t.Fatal(err)
	}

	waiting := pending.saved[0]
	pending.traces = map[string]pending_trace_repository.PendingTrace{pending_trace_repository.Id(1, "trace-1"): waiting}

	if _, err := New(store, pending).Save(context.Background(), batch(1, nil, updating(t))); err != nil {
		t.Fatal(err)
	}

	if len(store.keys) != 0 {
		t.Fatalf("ClickHouse was read although the create was pending: %v", store.keys)
	}

	row := store.inserted[0]

	if row.Type != "request" || row.Status != "success" || row.ParentTraceId != "parent-1" || *row.Duration != 0.2 {
		t.Fatalf("the halves were not merged: %+v", row)
	}

	if len(pending.forgot) != 0 || !isDoneMark(pending.saved) {
		t.Fatalf("the completed trace did not leave its done mark: %+v / %v", pending.saved, pending.forgot)
	}
}

func TestAnUpdateBeforeItsCreateWaitsOutsideClickHouse(t *testing.T) {
	store, pending := &fakeStore{}, &fakePending{}

	if _, err := New(store, pending).Save(context.Background(), batch(1, nil, updating(t))); err != nil {
		t.Fatal(err)
	}

	if len(store.keys) != 1 || len(store.inserted) != 0 {
		t.Fatalf("expected one fallback read and no insert, got %d keys, %d rows", len(store.keys), len(store.inserted))
	}

	if len(pending.saved) != 1 || !pending.saved[0].HasUpdate {
		t.Fatalf("the update was not kept pending: %+v", pending.saved)
	}

	pending.traces = map[string]pending_trace_repository.PendingTrace{pending_trace_repository.Id(1, "trace-1"): pending.saved[0]}

	if _, err := New(store, pending).Save(context.Background(), batch(1, creating(t), nil)); err != nil {
		t.Fatal(err)
	}

	row := store.inserted[0]

	if row.Type != "request" || row.Status != "success" || !isDoneMark(pending.saved) {
		t.Fatalf("the late create did not complete the trace: %+v, pending %+v", row, pending.saved)
	}
}

func TestALateUpdateIsMergedWithTheStoredRow(t *testing.T) {
	key := clickhouse_trace_repository.Key{ServiceId: 1, LoggedAtMicro: loggedAt.UnixMicro(), TraceId: "trace-1"}
	store := &fakeStore{
		stored: map[clickhouse_trace_repository.Key]clickhouse_trace_repository.StoredTrace{
			key: {Type: "request", Status: "started", CreatedAt: firstAt},
		},
	}
	pending := &fakePending{}

	if _, err := New(store, pending).Save(context.Background(), batch(1, nil, updating(t))); err != nil {
		t.Fatal(err)
	}

	if len(store.inserted) != 1 || store.inserted[0].Status != "success" || store.inserted[0].Type != "request" {
		t.Fatalf("the late update was not merged with the stored row: %+v", store.inserted)
	}
}

func TestSaveFailsTheWholeBatchWhenAStoreFails(t *testing.T) {
	cases := []struct {
		store   *fakeStore
		pending *fakePending
	}{
		{&fakeStore{findErr: errors.New("down")}, &fakePending{}},
		{&fakeStore{insertErr: errors.New("refused")}, &fakePending{}},
		{&fakeStore{}, &fakePending{findErr: errors.New("down")}},
		{&fakeStore{}, &fakePending{applyErr: errors.New("refused")}},
	}

	for _, c := range cases {
		if _, err := New(c.store, c.pending).Save(context.Background(), batch(1, nil, updating(t))); err == nil {
			t.Fatal("a failed store was not reported")
		}
	}
}

func TestATraceWithNeitherHalfFailsAlone(t *testing.T) {
	traces := batch(1, creating(t), updating(t))
	traces[1].AddId("empty", [12]byte{1})

	result, err := New(&fakeStore{}, &fakePending{}).Save(context.Background(), traces)

	if err != nil {
		t.Fatal(err)
	}

	if !result.Failed[1]["empty"] || result.Saved != 1 {
		t.Fatalf("unexpected result %+v", result)
	}
}

func doneMark() map[string]pending_trace_repository.PendingTrace {
	return map[string]pending_trace_repository.PendingTrace{
		pending_trace_repository.Id(1, "trace-1"): {ServiceId: 1, TraceId: "trace-1", Done: true},
	}
}

// A create arriving again after its trace came complete is a repeat. Merged as a new trace,
// it would reopen the finished trace as started and count it a second time.
func TestARepeatedCreateAfterTheTraceCameCompleteIsDropped(t *testing.T) {
	store, pending := &fakeStore{}, &fakePending{traces: doneMark()}

	result, err := New(store, pending).Save(context.Background(), batch(1, creating(t), nil))

	if err != nil {
		t.Fatal(err)
	}

	if result.Saved != 1 || len(store.inserted) != 0 || len(store.keys) != 0 || len(pending.saved) != 0 || len(pending.forgot) != 0 {
		t.Fatalf("the repeat was written: %+v, %d rows, %d keys, pending %+v / %v", result, len(store.inserted), len(store.keys), pending.saved, pending.forgot)
	}
}

// The whole sequence through Save: create with update, then the same create alone.
func TestACreateSentTwiceDoesNotReopenTheTrace(t *testing.T) {
	store, pending := &fakeStore{}, &fakePending{}

	if _, err := New(store, pending).Save(context.Background(), batch(1, creating(t), updating(t))); err != nil {
		t.Fatal(err)
	}

	pending.traces = map[string]pending_trace_repository.PendingTrace{pending_trace_repository.Id(1, "trace-1"): pending.saved[0]}
	store.inserted = nil

	if _, err := New(store, pending).Save(context.Background(), batch(1, creating(t), nil)); err != nil {
		t.Fatal(err)
	}

	if len(store.inserted) != 0 {
		t.Fatalf("the repeated create was written: %+v", store.inserted)
	}
}

// An update after the done mark merges with the stored trace, which only ClickHouse holds.
func TestAnUpdateAfterTheDoneMarkReadsTheStoredTrace(t *testing.T) {
	key := clickhouse_trace_repository.Key{ServiceId: 1, LoggedAtMicro: loggedAt.UnixMicro(), TraceId: "trace-1"}
	store := &fakeStore{
		stored: map[clickhouse_trace_repository.Key]clickhouse_trace_repository.StoredTrace{
			key: {Type: "request", Status: "success", Tags: []string{"api"}, CreatedAt: firstAt},
		},
	}
	pending := &fakePending{traces: doneMark()}

	update := updating(t)
	update.Status = "failed"

	if _, err := New(store, pending).Save(context.Background(), batch(1, creating(t), update)); err != nil {
		t.Fatal(err)
	}

	if len(store.keys) != 1 || len(store.inserted) != 1 {
		t.Fatalf("expected the stored trace read and one insert, got %d keys, %d rows", len(store.keys), len(store.inserted))
	}

	row := store.inserted[0]

	if row.Type != "request" || row.Status != "failed" || row.CreatedAt != firstAt.Format(clickhouse_trace_repository.TimeLayout) || !isDoneMark(pending.saved) {
		t.Fatalf("the update was not merged with the stored trace: %+v, pending %+v", row, pending.saved)
	}
}

// A create that brought no type is still a create: it is written under the placeholder
// rather than left waiting for a create that has already come.
func TestACreateWithoutATypeIsWritten(t *testing.T) {
	store, pending := &fakeStore{}, &fakePending{}

	trace := creating(t)
	trace.Type = ""
	trace.Status = "success"

	if _, err := New(store, pending).Save(context.Background(), batch(1, trace, nil)); err != nil {
		t.Fatal(err)
	}

	if len(store.inserted) != 1 || store.inserted[0].Type != unknownTraceType || len(pending.saved) != 0 {
		t.Fatalf("the create was not written: %+v, pending %+v", store.inserted, pending.saved)
	}
}

func boolean(value bool) *bool {
	return &value
}

func uint32Of(value uint32) *uint32 {
	return &value
}

func TestAParentIsKeptPendingWhateverItsStatus(t *testing.T) {
	store, pending := &fakeStore{}, &fakePending{}

	create := creating(t)
	create.Status = "running"
	create.IsParent = boolean(true)

	if _, err := New(store, pending).Save(context.Background(), batch(1, create, nil)); err != nil {
		t.Fatal(err)
	}

	if len(store.inserted) != 1 || store.inserted[0].Status != "running" {
		t.Fatalf("the parent was not inserted: %+v", store.inserted)
	}

	if len(pending.saved) != 1 || pending.saved[0].Done || pending.saved[0].HasUpdate {
		t.Fatalf("the parent was not kept pending: %+v", pending.saved)
	}
}

func TestACreateWithNoUpdateToComeIsNotKeptPending(t *testing.T) {
	store, pending := &fakeStore{}, &fakePending{}

	create := creating(t)
	create.IsParent = boolean(false)

	if _, err := New(store, pending).Save(context.Background(), batch(1, create, nil)); err != nil {
		t.Fatal(err)
	}

	if len(store.inserted) != 1 || store.inserted[0].Status != "started" {
		t.Fatalf("the create was not inserted: %+v", store.inserted)
	}

	if len(pending.saved) != 0 || len(pending.forgot) != 0 {
		t.Fatalf("the create was kept pending: %+v / %v", pending.saved, pending.forgot)
	}
}

func TestAFinishedCreateWithoutIsPIsNotKeptPending(t *testing.T) {
	store, pending := &fakeStore{}, &fakePending{}

	create := creating(t)
	create.Status = "success"

	if _, err := New(store, pending).Save(context.Background(), batch(1, create, nil)); err != nil {
		t.Fatal(err)
	}

	if len(store.inserted) != 1 || len(pending.saved) != 0 {
		t.Fatalf("expected an insert and nothing pending, got %+v / %+v", store.inserted, pending.saved)
	}
}

func TestThePidOfTheCreateOutlivesItsPendingWait(t *testing.T) {
	store, pending := &fakeStore{}, &fakePending{}

	create := creating(t)
	create.Pid = uint32Of(321)

	if _, err := New(store, pending).Save(context.Background(), batch(1, create, nil)); err != nil {
		t.Fatal(err)
	}

	if store.inserted[0].Pid == nil || *store.inserted[0].Pid != 321 || pending.saved[0].Pid == nil {
		t.Fatalf("the pid was not written and kept: %+v / %+v", store.inserted[0], pending.saved[0])
	}

	pending.traces = map[string]pending_trace_repository.PendingTrace{pending_trace_repository.Id(1, "trace-1"): pending.saved[0]}

	if _, err := New(store, pending).Save(context.Background(), batch(1, nil, updating(t))); err != nil {
		t.Fatal(err)
	}

	if row := store.inserted[0]; row.Status != "success" || row.Pid == nil || *row.Pid != 321 {
		t.Fatalf("the update lost the pid: %+v", row)
	}
}

func TestAnUpdateKeepsThePidStoredInClickHouse(t *testing.T) {
	merged := mergeTrace(
		1,
		"trace-1",
		&dto.Traces{Updating: updating(t)},
		&clickhouse_trace_repository.StoredTrace{Type: "request", Status: "started", Pid: uint32Of(77), CreatedAt: firstAt},
		loggedAt,
		secondAt,
	)

	if merged.row.Pid == nil || *merged.row.Pid != 77 {
		t.Fatalf("the stored pid was lost: %v", merged.row.Pid)
	}
}

func TestATraceWithoutAPidHasNone(t *testing.T) {
	merged := mergeTrace(1, "trace-1", &dto.Traces{Creating: creating(t), Updating: updating(t)}, nil, loggedAt, firstAt)

	if merged.row.Pid != nil {
		t.Fatalf("a pid appeared from nowhere: %v", *merged.row.Pid)
	}
}
