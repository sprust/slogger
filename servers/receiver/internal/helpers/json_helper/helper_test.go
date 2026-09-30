package json_helper

import (
	"math"
	"testing"

	"go.mongodb.org/mongo-driver/bson"
)

func TestKeepsTheOrderOfObjects(t *testing.T) {
	value := bson.D{
		{Key: "sql", Value: "select 1"},
		{Key: "connection", Value: bson.D{{Key: "z", Value: 1.0}, {Key: "a", Value: nil}}},
		{Key: "bindings", Value: bson.A{"x", 2.5, true, bson.D{{Key: "b", Value: "c"}, {Key: "a", Value: "d"}}}},
	}

	assertJson(t, value, `{"sql":"select 1","connection":{"z":1,"a":null},"bindings":["x",2.5,true,{"b":"c","a":"d"}]}`)
}

func TestRoundTripsADocument(t *testing.T) {
	source := `{"b":1,"a":{"y":[1,{"q":null,"p":"<tag>&"}],"x":false},"c":""}`

	var data bson.D

	if err := bson.UnmarshalExtJSON([]byte(source), false, &data); err != nil {
		t.Fatal(err)
	}

	assertJson(t, data, source)
}

func TestWritesScalarsAndEmptyValues(t *testing.T) {
	assertJson(t, nil, `null`)
	assertJson(t, "text", `"text"`)
	assertJson(t, 0.137, `0.137`)
	assertJson(t, bson.D{}, `{}`)
	assertJson(t, bson.A{}, `[]`)
	assertJson(t, []interface{}{}, `[]`)
}

func TestSortsTheKeysOfAMap(t *testing.T) {
	assertJson(t, map[string]interface{}{"b": 1.0, "a": bson.M{"d": 2.0, "c": 3.0}}, `{"a":{"c":3,"d":2},"b":1}`)
}

func TestRefusesWhatJsonCannotHold(t *testing.T) {
	if _, err := Marshal(bson.D{{Key: "x", Value: math.Inf(1)}}); err == nil {
		t.Fatal("an infinity was written")
	}
}

func assertJson(t *testing.T, value interface{}, expected string) {
	t.Helper()

	encoded, err := Marshal(value)

	if err != nil {
		t.Fatal(err)
	}

	if string(encoded) != expected {
		t.Fatalf("expected %s, got %s", expected, encoded)
	}
}
