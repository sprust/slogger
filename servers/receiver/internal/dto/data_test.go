package dto

import (
	"encoding/json"
	"testing"
	"unicode/utf8"
)

func decodeData(t *testing.T, payload string) Data {
	t.Helper()

	var data Data

	if err := json.Unmarshal([]byte(payload), &data); err != nil {
		t.Fatal(err)
	}

	return data
}

// Order, numbers, a repeated key and escapes all live in the bytes, and the bytes are kept.
func TestTheDataIsKeptAsItWasSent(t *testing.T) {
	payloads := []string{
		`{"sql":"select 1","connection":"mysql","bindings":[],"__trace":"x","__add":1,"aaa":2}`,
		`{"z":{"y":1,"b":2},"a":[{"q":1,"p":2}]}`,
		`{"id":9007199254740993,"amount":500.0,"rate":1.50,"small":1e-7}`,
		`{"a":1,"b":2,"a":3}`,
		`{"path":"\/x","name":"Ж","html":"<b>&"}`,
	}

	for _, payload := range payloads {
		if data := decodeData(t, payload); string(data.Raw) != payload {
			t.Fatalf("expected %s, got %s", payload, data.Raw)
		}
	}
}

func TestAValueThatIsNotAnObject(t *testing.T) {
	for _, payload := range []string{`"text"`, `7`, `true`, `[1,"two"]`, `{}`, `[]`} {
		if data := decodeData(t, payload); string(data.Raw) != payload {
			t.Fatalf("expected %s, got %s", payload, data.Raw)
		}
	}
}

// The message is what is validated: a broken dt is a broken message.
func TestABrokenMessageIsAnError(t *testing.T) {
	for _, payload := range []string{
		`[{"tid":"1","dt":{"a":}]`,
		`[{"tid":"1","dt":{"a":1} {"b":2}}]`,
		`[{"tid":"1","dt":{]`,
	} {
		var creating []TraceCreating

		if err := json.Unmarshal([]byte(payload), &creating); err == nil {
			t.Fatalf("%s decoded", payload)
		}
	}
}

// The whole message, so a wrong struct tag would show.
func TestATraceMessageCarriesItsData(t *testing.T) {
	var creating []TraceCreating

	payload := `[{"tid":"1","tp":"http","st":"success","tgs":[],"dt":{"url":"/x","method":"GET","code":200},"lat":"2026-09-19 03:04:05"}]`

	if err := json.Unmarshal([]byte(payload), &creating); err != nil {
		t.Fatal(err)
	}

	if expected := `{"url":"/x","method":"GET","code":200}`; string(creating[0].Data.Raw) != expected {
		t.Fatalf("expected %s, got %s", expected, creating[0].Data.Raw)
	}
}

// The message buffer is reused, so the data must be its own copy.
func TestTheDataIsACopy(t *testing.T) {
	payload := []byte(`[{"tid":"1","st":"success","dt":{"a":1},"plat":"2026-09-19 03:04:05"}]`)

	var updating []TraceUpdating

	if err := json.Unmarshal(payload, &updating); err != nil {
		t.Fatal(err)
	}

	for index := range payload {
		payload[index] = ' '
	}

	if string(updating[0].Data.Raw) != `{"a":1}` {
		t.Fatalf("the data changed with the message: %s", updating[0].Data.Raw)
	}
}

func TestATraceWithoutDataCarriesNothing(t *testing.T) {
	var updating []TraceUpdating

	if err := json.Unmarshal([]byte(`[{"tid":"1","st":"success","plat":"2026-09-19 03:04:05"},{"tid":"2","st":"success","dt":null,"plat":"2026-09-19 03:04:05"}]`), &updating); err != nil {
		t.Fatal(err)
	}

	for _, trace := range updating {
		if !trace.Data.IsNull() {
			t.Fatalf("trace %s: expected no data, got %s", trace.TraceId, trace.Data.Raw)
		}
	}
}

func TestWhatCountsAsEmpty(t *testing.T) {
	cases := map[string]bool{
		``:          true,
		`null`:      true,
		` null `:    true,
		`[]`:        true,
		`{}`:        true,
		`[ ]`:       true,
		`{ }`:       true,
		`[0]`:       false,
		`{"a":1}`:   false,
		`""`:        false,
		`0`:         false,
		`false`:     false,
		`"[]"`:      false,
		`[1,"two"]`: false,
	}

	for raw, expected := range cases {
		if IsEmptyJson([]byte(raw)) != expected {
			t.Fatalf("%q: expected empty = %v", raw, expected)
		}
	}
}

func TestWhatCountsAsAnObject(t *testing.T) {
	cases := map[string]bool{
		`{"a":1}`: true,
		` {}`:     true,
		`[{}]`:    false,
		`"{"`:     false,
		`null`:    false,
		``:        false,
	}

	for raw, expected := range cases {
		if IsObjectJson([]byte(raw)) != expected {
			t.Fatalf("%q: expected object = %v", raw, expected)
		}
	}
}

// ClickHouse refuses a whole insert over one invalid byte in a JSON column.
func TestInvalidUtf8BecomesTheReplacementCharacter(t *testing.T) {
	var creating []TraceCreating

	if err := json.Unmarshal([]byte("[{\"tid\":\"1\",\"dt\":{\"a\":\"bad \xff\xfe here\"}}]"), &creating); err != nil {
		t.Fatal(err)
	}

	raw := creating[0].Data.Raw

	if !utf8.Valid(raw) || !json.Valid(raw) || string(raw) != "{\"a\":\"bad \uFFFD here\"}" {
		t.Fatalf("got %q", raw)
	}
}

func TestACreateCarriesIsPAndPid(t *testing.T) {
	var creating []TraceCreating

	payload := `[{"tid":"1","isP":true,"pid":4194304},{"tid":"2","isP":false,"pid":null},{"tid":"3"}]`

	if err := json.Unmarshal([]byte(payload), &creating); err != nil {
		t.Fatal(err)
	}

	if creating[0].IsParent == nil || !*creating[0].IsParent || creating[0].Pid == nil || *creating[0].Pid != 4194304 {
		t.Fatalf("trace 1: isP %v, pid %v", creating[0].IsParent, creating[0].Pid)
	}

	if creating[1].IsParent == nil || *creating[1].IsParent || creating[1].Pid != nil {
		t.Fatalf("trace 2: isP %v, pid %v", creating[1].IsParent, creating[1].Pid)
	}

	if creating[2].IsParent != nil || creating[2].Pid != nil {
		t.Fatalf("trace 3: isP %v, pid %v", creating[2].IsParent, creating[2].Pid)
	}
}
