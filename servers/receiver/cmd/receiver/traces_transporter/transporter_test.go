package traces_transporter

import (
	"slogger_receiver/internal/dto"
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
