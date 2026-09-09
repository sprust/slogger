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

// A trace whose update was persisted first has no type on that write, and the duration it
// brought must wait for the create rather than go over under the placeholder: a filtered
// watcher never sees __UNKNOWN, and an unfiltered one would file the evidence under a type
// that does not exist.
func TestTheDurationWaitsForARealType(t *testing.T) {
	cases := []struct {
		name              string
		typeIsKnown       bool
		typeWasKnown      bool
		durationWasStored bool
		want              bool
	}{
		{"a create bringing both", true, false, false, true},
		{"an update completing a create", true, true, false, true},
		{"an update landing before its create", false, false, false, false},
		{"the create picking up a duration stored under the placeholder", true, false, true, true},
		{"a replayed batch reporting nothing again", true, true, true, false},
	}

	for _, testCase := range cases {
		t.Run(testCase.name, func(t *testing.T) {
			got := reportsDuration(testCase.typeIsKnown, testCase.typeWasKnown, testCase.durationWasStored)

			if got != testCase.want {
				t.Fatalf("reportsDuration = %v, wanted %v", got, testCase.want)
			}
		})
	}
}
