package bson_helper

import (
	"reflect"
	"testing"

	"go.mongodb.org/mongo-driver/bson"
)

func rawOf(t *testing.T, document bson.M) bson.Raw {
	t.Helper()

	raw, err := bson.Marshal(document)

	if err != nil {
		t.Fatal(err)
	}

	return raw
}

// The same document read through a map and through its bytes: the map is what loses the
// order, which is the reason this helper exists.
func TestTheStoredOrderIsReadBack(t *testing.T) {
	stored := bson.D{
		{Key: "sql", Value: "select 1"},
		{Key: "connection", Value: "mysql"},
		{Key: "__add", Value: float64(1)},
	}

	raw := rawOf(t, bson.M{"dt": stored})

	value, ok := OrderedValue(raw, "dt", nil).(bson.D)

	if !ok {
		t.Fatalf("expected a document, got %T", OrderedValue(raw, "dt", nil))
	}

	if !reflect.DeepEqual(value, stored) {
		t.Fatalf("expected %v, got %v", stored, value)
	}
}

func TestAMissingFieldFallsBack(t *testing.T) {
	raw := rawOf(t, bson.M{"st": "success"})

	fallback := bson.M{"code": 200}

	if value := OrderedValue(raw, "dt", fallback); !reflect.DeepEqual(value, fallback) {
		t.Fatalf("expected the fallback, got %v", value)
	}
}

// A trace saved before its data arrived carries `dt` as a null, and mergeData reads that
// null as "no data". Handing back anything but nil would make it data.
func TestANullFieldIsNothing(t *testing.T) {
	raw := rawOf(t, bson.M{"dt": nil})

	if value := OrderedValue(raw, "dt", bson.M{"code": 200}); value != nil {
		t.Fatalf("expected nil, got %v", value)
	}
}

// The trace does not exist yet, so there are no bytes to read. Reached on every create.
func TestAnEmptyDocumentFallsBack(t *testing.T) {
	if value := OrderedValue(nil, "dt", nil); value != nil {
		t.Fatalf("expected nil, got %v", value)
	}
}

func TestAnArrayKeepsTheOrderOfItsObjects(t *testing.T) {
	stored := bson.A{
		bson.D{{Key: "q", Value: float64(1)}, {Key: "p", Value: float64(2)}},
	}

	raw := rawOf(t, bson.M{"dt": stored})

	if value := OrderedValue(raw, "dt", nil); !reflect.DeepEqual(value, stored) {
		t.Fatalf("expected %v, got %v", stored, value)
	}
}
