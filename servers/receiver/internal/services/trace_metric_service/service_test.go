package trace_metric_service

import (
	"context"
	"slogger_receiver/internal/repositories/trace_metric_repository"
	"testing"
	"time"
)

type recordingPusher struct {
	pushed []map[trace_metric_repository.Key]trace_metric_repository.Counts
}

func (p *recordingPusher) Push(_ context.Context, counts map[trace_metric_repository.Key]trace_metric_repository.Counts) error {
	p.pushed = append(p.pushed, counts)

	return nil
}

func slot(value string) int64 {
	at, _ := time.Parse(time.DateTime, value)

	return at.UTC().UnixMilli()
}

func at(value string) time.Time {
	parsed, _ := time.Parse(time.DateTime, value)

	return parsed.UTC()
}

func key(serviceId int, traceType string, slotStart string) trace_metric_repository.Key {
	return trace_metric_repository.Key{ServiceId: serviceId, Type: traceType, At: slot(slotStart)}
}

// Three clocks in one slot are one document with three counters, not three documents.
func TestClocksInTheSameSlotShareOneKey(t *testing.T) {
	service := newService(&recordingPusher{})

	service.AddTrace(1, "http", at("2026-09-11 12:01:10"), at("2026-09-11 12:01:11"), at("2026-09-11 12:01:12"))

	counts := service.takeCounts()

	if len(counts) != 1 {
		t.Fatalf("expected one key, got %d", len(counts))
	}

	got := counts[key(1, "http", "2026-09-11 12:00:00")]

	if got != (trace_metric_repository.Counts{Logged: 1, Buffered: 1, Stored: 1}) {
		t.Fatalf("expected one of each, got %+v", got)
	}
}

// A buffer that backed up spreads a trace over slots: each clock counts where it fell.
func TestClocksInDifferentSlotsCountWhereTheyFell(t *testing.T) {
	service := newService(&recordingPusher{})

	service.AddTrace(1, "http", at("2026-09-11 12:03:00"), at("2026-09-11 12:14:59"), at("2026-09-11 12:21:00"))

	counts := service.takeCounts()

	if len(counts) != 2 {
		t.Fatalf("expected two keys, got %d", len(counts))
	}

	first := counts[key(1, "http", "2026-09-11 12:00:00")]
	later := counts[key(1, "http", "2026-09-11 12:15:00")]

	if first != (trace_metric_repository.Counts{Logged: 1, Buffered: 1}) {
		t.Fatalf("expected logged and buffered in the first slot, got %+v", first)
	}

	if later != (trace_metric_repository.Counts{Stored: 1}) {
		t.Fatalf("expected stored in the later slot, got %+v", later)
	}
}

func TestTracesOfOneKeyAddUp(t *testing.T) {
	service := newService(&recordingPusher{})

	for i := 0; i < 3; i++ {
		service.AddTrace(1, "http", at("2026-09-11 12:01:00"), at("2026-09-11 12:01:00"), at("2026-09-11 12:01:00"))
	}

	service.AddTrace(1, "job", at("2026-09-11 12:01:00"), at("2026-09-11 12:01:00"), at("2026-09-11 12:01:00"))
	service.AddTrace(2, "http", at("2026-09-11 12:01:00"), at("2026-09-11 12:01:00"), at("2026-09-11 12:01:00"))

	counts := service.takeCounts()

	if got := counts[key(1, "http", "2026-09-11 12:00:00")]; got.Stored != 3 {
		t.Fatalf("expected three traces of one shape to add up, got %+v", got)
	}

	if len(counts) != 3 {
		t.Fatalf("expected a key per service and type, got %d", len(counts))
	}
}

// A client clock running fast is counted as the moment the trace was written.
func TestALoggingTimeFromTheFutureIsCountedAsNow(t *testing.T) {
	service := newService(&recordingPusher{})

	service.AddTrace(1, "http", at("2026-09-11 13:30:00"), at("2026-09-11 12:01:00"), at("2026-09-11 12:01:00"))

	counts := service.takeCounts()

	if got := counts[key(1, "http", "2026-09-11 12:00:00")]; got.Logged != 1 {
		t.Fatalf("expected the future logging time in the current slot, got %+v", counts)
	}
}

// Logged longer ago than the panel keeps anything: its slot would be retired on arrival.
func TestALoggingTimeOlderThanTheRetentionIsNotCounted(t *testing.T) {
	service := newService(&recordingPusher{})

	service.AddTrace(1, "http", at("2026-09-10 10:00:00"), at("2026-09-11 12:01:00"), at("2026-09-11 12:01:00"))

	counts := service.takeCounts()

	if len(counts) != 1 {
		t.Fatalf("expected only the current slot, got %+v", counts)
	}

	if got := counts[key(1, "http", "2026-09-11 12:00:00")]; got != (trace_metric_repository.Counts{Buffered: 1, Stored: 1}) {
		t.Fatalf("expected buffered and stored only, got %+v", got)
	}
}

// A buffer document from before the receiving time was read carries none.
func TestAMissingReceivingTimeFallsBackToTheStoringTime(t *testing.T) {
	service := newService(&recordingPusher{})

	service.AddTrace(1, "http", at("2026-09-11 12:01:00"), time.Time{}, at("2026-09-11 12:16:00"))

	counts := service.takeCounts()

	if got := counts[key(1, "http", "2026-09-11 12:15:00")]; got != (trace_metric_repository.Counts{Buffered: 1, Stored: 1}) {
		t.Fatalf("expected the buffered count beside the stored one, got %+v", counts)
	}
}

func TestAFlushHandsEverythingOverAndStartsAgain(t *testing.T) {
	pusher := &recordingPusher{}
	service := newService(pusher)

	service.AddTrace(1, "http", at("2026-09-11 12:01:00"), at("2026-09-11 12:01:00"), at("2026-09-11 12:01:00"))

	if err := service.Flush(context.Background()); err != nil {
		t.Fatal(err)
	}

	if err := service.Flush(context.Background()); err != nil {
		t.Fatal(err)
	}

	if len(pusher.pushed) != 1 {
		t.Fatalf("expected one push and an empty second flush, got %d pushes", len(pusher.pushed))
	}

	if len(service.takeCounts()) != 0 {
		t.Fatal("expected nothing left after a flush")
	}
}
