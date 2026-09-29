package periodic_trace_service

import (
	"context"
	"encoding/json"
	"errors"
	"reflect"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/repositories/clickhouse_trace_repository"
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
		CreatedAt:     createdAt,
	}
}

func TestCreateAndUpdateInOneBatch(t *testing.T) {
	merged, err := mergeTrace(1, "trace-1", &dto.Traces{Creating: creating(t), Updating: updating(t)}, nil, loggedAt, firstAt)

	if err != nil {
		t.Fatal(err)
	}

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
	first, err := mergeTrace(1, "trace-1", &dto.Traces{Updating: updating(t)}, nil, loggedAt, firstAt)

	if err != nil {
		t.Fatal(err)
	}

	if first.row.Type != unknownTraceType || first.countsAsNew || first.newDuration != nil {
		t.Fatalf("an update alone was counted: %+v", first)
	}

	second, err := mergeTrace(1, "trace-1", &dto.Traces{Creating: creating(t)}, storedFrom(first.row), loggedAt, secondAt)

	if err != nil {
		t.Fatal(err)
	}

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
	first, _ := mergeTrace(1, "trace-1", &dto.Traces{Creating: creating(t), Updating: updating(t)}, nil, loggedAt, firstAt)

	second, err := mergeTrace(1, "trace-1", &dto.Traces{Creating: creating(t)}, storedFrom(first.row), loggedAt, secondAt)

	if err != nil {
		t.Fatal(err)
	}

	if second.row.Status != "success" || *second.row.Duration != 0.2 || second.row.RawData != `{"code":200}` {
		t.Fatalf("a repeated create overwrote the trace: %+v", second.row)
	}

	if second.countsAsNew || second.newDuration != nil {
		t.Fatal("a repeated create was counted again")
	}
}

func TestCreateWithoutUpdate(t *testing.T) {
	merged, err := mergeTrace(1, "trace-1", &dto.Traces{Creating: creating(t)}, nil, loggedAt, firstAt)

	if err != nil {
		t.Fatal(err)
	}

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

	merged, err := mergeTrace(1, "trace-1", &dto.Traces{Creating: trace}, nil, loggedAt, firstAt)

	if err != nil {
		t.Fatal(err)
	}

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

func batchOf(t *testing.T) map[int]*dto.ServiceTraces {
	traces := &dto.ServiceTraces{}
	traces.AddUpdating(updating(t))

	lonely := &dto.ServiceTraces{}
	lonely.AddCreating(creating(t))

	return map[int]*dto.ServiceTraces{1: traces, 2: lonely}
}

func TestSaveReadsOnceAndWritesOnce(t *testing.T) {
	store := &fakeStore{
		stored: map[clickhouse_trace_repository.Key]clickhouse_trace_repository.StoredTrace{
			{ServiceId: 1, LoggedAtMicro: loggedAt.UnixMicro(), TraceId: "trace-1"}: {Type: "request", Status: "started", CreatedAt: firstAt},
		},
	}

	result, err := New(store).Save(context.Background(), batchOf(t))

	if err != nil {
		t.Fatal(err)
	}

	if result.Saved != 2 || len(store.keys) != 2 || len(store.inserted) != 2 {
		t.Fatalf("expected one read and one write of two traces, got %+v, %d keys, %d rows", result, len(store.keys), len(store.inserted))
	}

	for _, row := range store.inserted {
		if row.ServiceId == 1 && (row.Type != "request" || row.Status != "success") {
			t.Fatalf("the stored half was not merged in: %+v", row)
		}
	}
}

func TestSaveFailsTheWholeBatchWhenTheStoreFails(t *testing.T) {
	for _, store := range []*fakeStore{{findErr: errors.New("down")}, {insertErr: errors.New("refused")}} {
		if _, err := New(store).Save(context.Background(), batchOf(t)); err == nil {
			t.Fatal("a failed store was not reported")
		}
	}
}

func TestATraceWithNeitherHalfFailsAlone(t *testing.T) {
	batch := batchOf(t)
	batch[1].AddId("empty", [12]byte{1})

	store := &fakeStore{}

	result, err := New(store).Save(context.Background(), batch)

	if err != nil {
		t.Fatal(err)
	}

	if !result.Failed[1]["empty"] || result.Saved != 2 {
		t.Fatalf("unexpected result %+v", result)
	}
}
