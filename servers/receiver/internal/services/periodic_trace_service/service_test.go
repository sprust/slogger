package periodic_trace_service

import (
	"encoding/json"
	"testing"
)

// A trace whose update was persisted before its create wears the placeholder until the
// create arrives. Counting it then would file it under a type no filter matches, and the
// create that follows could not correct it.
func TestThePlaceholderIsNotAKnownType(t *testing.T) {
	if isKnownTraceType(unknownTraceType) {
		t.Fatal("the placeholder passed as a real type")
	}

	if isKnownTraceType("") {
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

func raw(value string) json.RawMessage {
	if value == "" {
		return nil
	}

	return json.RawMessage(value)
}

// The table the merge picks the data by: update, else stored unless empty, else create,
// else stored, else an empty list. "" stands for a field that is not there.
func TestMergeDataPicksByTheTable(t *testing.T) {
	cases := []struct {
		name     string
		updating string
		existing string
		creating string
		want     string
	}{
		{"the update wins over everything", `{"code":500}`, `{"code":200}`, `{"path":"/x"}`, `{"code":500}`},
		{"an update of null brings nothing", `null`, `{"code":200}`, `{"path":"/x"}`, `{"code":200}`},
		{"stored data wins over the create", ``, `{"code":200}`, `{"path":"/x"}`, `{"code":200}`},
		{"an empty stored list does not hide a late create", ``, `[]`, `{"path":"/x"}`, `{"path":"/x"}`},
		{"an empty stored object does not hide a late create", ``, `{ }`, `{"path":"/x"}`, `{"path":"/x"}`},
		{"stored null does not hide a late create", ``, `null`, `{"path":"/x"}`, `{"path":"/x"}`},
		{"an empty stored object stays when nothing else comes", ``, `{}`, ``, `{}`},
		{"a create of null leaves the stored empty list", ``, `[]`, `null`, `[]`},
		{"an empty update still wins", `[]`, `{"code":200}`, ``, `[]`},
		{"no data anywhere is an empty list", ``, ``, ``, `[]`},
	}

	for _, testCase := range cases {
		t.Run(testCase.name, func(t *testing.T) {
			got := mergeData(raw(testCase.updating), raw(testCase.existing), raw(testCase.creating))

			if string(got) != testCase.want {
				t.Fatalf("expected %s, got %s", testCase.want, got)
			}
		})
	}
}

// Stored data goes back as its bytes, so an update without data cannot reorder it.
func TestStoredDataKeepsItsOrder(t *testing.T) {
	stored := `{"sql":"select 1","connection":"mysql"}`

	if got := mergeData(nil, raw(stored), raw(`{"path":"/x"}`)); string(got) != stored {
		t.Fatalf("expected the stored data, got %s", got)
	}
}
