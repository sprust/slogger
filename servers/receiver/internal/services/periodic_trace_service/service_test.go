package periodic_trace_service

import (
	"testing"

	"go.mongodb.org/mongo-driver/bson"
)

// The document is written with every field it has room for, so a trace saved before its
// duration arrived carries `dur` as a null rather than not carrying it at all. Deciding
// "has a duration already been stored?" by the key therefore answered yes for every write
// after the first — and no duration was ever handed to the watchers, which is every
// slow_traces watcher silently going quiet.
func TestAStoredNullDurationIsNotAStoredDuration(t *testing.T) {
	stored := bson.M{"dur": nil}

	if _, present := stored["dur"]; !present {
		t.Fatal("the key is there — that is the whole trap")
	}

	if durationValue(stored["dur"]) != nil {
		t.Fatal("a null duration read as a duration")
	}
}

func TestAStoredDurationIsReadBack(t *testing.T) {
	stored := bson.M{"dur": 12.5}

	duration := durationValue(stored["dur"])

	if duration == nil || *duration != 12.5 {
		t.Fatalf("expected 12.5, got %v", duration)
	}
}

// A trace whose update was persisted before its create wears the placeholder until the
// create arrives. Counting it then would file it under a type no filter matches, and the
// create that follows could not correct it.
func TestThePlaceholderIsNotAKnownType(t *testing.T) {
	if isKnownTraceType(unknownTraceType) {
		t.Fatal("the placeholder passed as a real type")
	}

	if isKnownTraceType("") || isKnownTraceType(nil) {
		t.Fatal("an absent type passed as a real type")
	}

	if !isKnownTraceType("http") {
		t.Fatal("a real type was not recognised")
	}
}
