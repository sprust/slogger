package dto

import (
	"encoding/json"
	"reflect"
	"testing"

	"go.mongodb.org/mongo-driver/bson"
)

func documentKeys(value interface{}) []string {
	document, ok := value.(bson.D)

	if !ok {
		return nil
	}

	keys := make([]string, 0, len(document))

	for _, element := range document {
		keys = append(keys, element.Key)
	}

	return keys
}

// The order the client sent is the whole point. A map would answer this test correctly
// about one key in six by luck, so the keys are in no order any sort would produce.
func TestTheFieldOrderSurvivesTheDecode(t *testing.T) {
	var data Data

	if err := json.Unmarshal([]byte(`{"sql":"select 1","connection":"mysql","bindings":[],"__trace":"x","__add":1,"aaa":2}`), &data); err != nil {
		t.Fatal(err)
	}

	keys := documentKeys(data.Value)
	expected := []string{"sql", "connection", "bindings", "__trace", "__add", "aaa"}

	if !reflect.DeepEqual(keys, expected) {
		t.Fatalf("expected %v, got %v", expected, keys)
	}
}

func TestNestedObjectsKeepTheirOrder(t *testing.T) {
	var data Data

	if err := json.Unmarshal([]byte(`{"z":{"y":1,"b":2},"a":[{"q":1,"p":2}]}`), &data); err != nil {
		t.Fatal(err)
	}

	document, ok := data.Value.(bson.D)

	if !ok {
		t.Fatalf("expected a document, got %T", data.Value)
	}

	if keys := documentKeys(document[0].Value); !reflect.DeepEqual(keys, []string{"y", "b"}) {
		t.Fatalf("nested object: got %v", keys)
	}

	array, ok := document[1].Value.(bson.A)

	if !ok || len(array) != 1 {
		t.Fatalf("expected an array of one, got %v", document[1].Value)
	}

	if keys := documentKeys(array[0]); !reflect.DeepEqual(keys, []string{"q", "p"}) {
		t.Fatalf("object inside an array: got %v", keys)
	}
}

// Reading numbers as integers would change the BSON types the dynamic indexes and the
// data filters are built on. This is about order and nothing else.
func TestNumbersStayFloats(t *testing.T) {
	var data Data

	if err := json.Unmarshal([]byte(`{"code":200,"duration":1.5}`), &data); err != nil {
		t.Fatal(err)
	}

	document := data.Value.(bson.D)

	for _, element := range document {
		if _, ok := element.Value.(float64); !ok {
			t.Fatalf("%s decoded as %T", element.Key, element.Value)
		}
	}
}

// A map kept one value per key whatever the payload said. A document can hold the key
// twice, which is a shape nothing downstream has ever seen.
func TestARepeatedKeyKeepsItsPlaceAndTakesTheLastValue(t *testing.T) {
	var data Data

	if err := json.Unmarshal([]byte(`{"a":1,"b":2,"a":3}`), &data); err != nil {
		t.Fatal(err)
	}

	expected := bson.D{{Key: "a", Value: float64(3)}, {Key: "b", Value: float64(2)}}

	if !reflect.DeepEqual(data.Value, expected) {
		t.Fatalf("expected %v, got %v", expected, data.Value)
	}
}

func TestAValueThatIsNotAnObject(t *testing.T) {
	cases := map[string]interface{}{
		`null`:      nil,
		`"text"`:    "text",
		`7`:         float64(7),
		`true`:      true,
		`[1,"two"]`: bson.A{float64(1), "two"},
		`{}`:        bson.D{},
		`[]`:        bson.A{},
	}

	for payload, expected := range cases {
		var data Data

		if err := json.Unmarshal([]byte(payload), &data); err != nil {
			t.Fatalf("%s: %v", payload, err)
		}

		if !reflect.DeepEqual(data.Value, expected) {
			t.Fatalf("%s: expected %v, got %v", payload, expected, data.Value)
		}
	}
}

func TestBrokenPayloadsAreErrors(t *testing.T) {
	for _, payload := range []string{`{"a":`, `{"a":1} {"b":2}`, `{`, `}`} {
		var data Data

		if err := json.Unmarshal([]byte(payload), &data); err == nil {
			t.Fatalf("%s decoded into %v", payload, data.Value)
		}
	}
}

// The message, not just the field: the trace list is decoded into these structs, and a
// field that decodes on its own can still be wired into the struct by the wrong tag.
func TestATraceMessageCarriesItsDataInOrder(t *testing.T) {
	var creating []TraceCreating

	payload := `[{"tid":"1","tp":"http","st":"success","tgs":[],"dt":{"url":"/x","method":"GET","code":200},"lat":"2026-09-19 03:04:05"}]`

	if err := json.Unmarshal([]byte(payload), &creating); err != nil {
		t.Fatal(err)
	}

	keys := documentKeys(creating[0].Data.Value)
	expected := []string{"url", "method", "code"}

	if !reflect.DeepEqual(keys, expected) {
		t.Fatalf("expected %v, got %v", expected, keys)
	}
}

func TestATraceWithoutDataCarriesNothing(t *testing.T) {
	var updating []TraceUpdating

	if err := json.Unmarshal([]byte(`[{"tid":"1","st":"success","plat":"2026-09-19 03:04:05"}]`), &updating); err != nil {
		t.Fatal(err)
	}

	if updating[0].Data.Value != nil {
		t.Fatalf("expected no data, got %v", updating[0].Data.Value)
	}
}
