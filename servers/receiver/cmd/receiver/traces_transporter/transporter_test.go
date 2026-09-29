package traces_transporter

import (
	"errors"
	"slogger_receiver/internal/dto"
	"slogger_receiver/pkg/foundation/errs"
	"testing"

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
	unavailable := []string{
		`Post "http://clickhouse:8123/?database=slogger": dial tcp 172.18.0.5:8123: connect: connection refused`,
		"clickhouse: Code: 241. DB::Exception: (total) memory limit exceeded: would use 4.01 GiB",
		"clickhouse: Code: 252. DB::Exception: Too many parts (3001) in table",
		"server selection error: context deadline exceeded",
	}

	for _, message := range unavailable {
		if !isUnavailable(errs.Err(errors.New(message))) {
			t.Fatalf("taken for a fault of the batch: %s", message)
		}
	}

	faults := []string{
		"clickhouse: Code: 27. DB::Exception: Cannot parse input: expected '\"' before: 'x'",
		"clickhouse: Code: 53. DB::Exception: Type mismatch",
	}

	for _, message := range faults {
		if isUnavailable(errs.Err(errors.New(message))) {
			t.Fatalf("taken for an outage: %s", message)
		}
	}
}
