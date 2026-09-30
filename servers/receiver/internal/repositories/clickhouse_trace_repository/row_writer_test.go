package clickhouse_trace_repository

import (
	"bytes"
	"encoding/json"
	"math"
	"math/rand/v2"
	"testing"
)

func float(value float64) *float64 {
	return &value
}

// encoded is what the rows were written as before appendRow: json.Encoder, HTML escaping off.
func encoded(t *testing.T, row Row) string {
	t.Helper()

	buffer := &bytes.Buffer{}
	encoder := json.NewEncoder(buffer)
	encoder.SetEscapeHTML(false)

	if err := encoder.Encode(&row); err != nil {
		t.Fatal(err)
	}

	return buffer.String()
}

func written(t *testing.T, row Row) string {
	t.Helper()

	line, err := appendRow(nil, &row)

	if err != nil {
		t.Fatal(err)
	}

	return string(line)
}

func sampleRow() Row {
	return Row{
		ServiceId:     42,
		TraceId:       "trace-1",
		ParentTraceId: "",
		Type:          "request",
		Status:        "success",
		Tags:          []string{"api", "/x?a=1&b=<2>"},
		Data:          json.RawMessage(`{"b":1,"a":{"y":[1,{"q":null,"p":"<tag>&"}],"x":false},"c":"Ж"}`),
		RawData:       `{"b":1,"a":{"y":[1,{"q":null,"p":"<tag>&"}],"x":false},"c":"Ж"}`,
		Duration:      float(0.2),
		Memory:        nil,
		Cpu:           float(35.12),
		LoggedAt:      "2026-09-29 10:00:00.123456",
		CreatedAt:     "2026-09-29 10:00:01.000000",
		UpdatedAt:     "2026-09-29 10:00:05.000000",
	}
}

func TestARowIsWrittenAsTheEncoderWroteIt(t *testing.T) {
	strings := []string{
		"",
		`quote " and backslash \ and slash /`,
		"line\nbreak\r\ttab \b\f \x00 \x1f \x7f",
		"юникод, 中文, emoji 😀",
		"invalid \xff\xfe utf-8 \xc3",
		"separators \u2028 and \u2029",
		"<script>&amp;</script>",
	}

	for _, value := range strings {
		row := sampleRow()
		row.TraceId = value
		row.Tags = []string{value}
		row.RawData = value

		if got, want := written(t, row), encoded(t, row); got != want {
			t.Fatalf("%q:\n got %s\nwant %s", value, got, want)
		}
	}
}

func TestNumbersAreWrittenAsTheEncoderWroteThem(t *testing.T) {
	numbers := []float64{0, -0.0, 1, -1, 0.2, 1.5e-7, 1e-6, 123456789.123, 1e20, 1e21, 1.7976931348623157e308, 5e-324, -3.25e-9}

	for _, number := range numbers {
		row := sampleRow()
		row.Duration = float(number)

		if got, want := written(t, row), encoded(t, row); got != want {
			t.Fatalf("%v:\n got %s\nwant %s", number, got, want)
		}
	}
}

func TestRandomStringsAreWrittenAsTheEncoderWroteThem(t *testing.T) {
	random := rand.New(rand.NewPCG(1, 2))

	for range 2000 {
		value := make([]byte, random.IntN(40))

		for index := range value {
			value[index] = byte(random.IntN(256))
		}

		row := sampleRow()
		row.RawData = string(value)

		if got, want := written(t, row), encoded(t, row); got != want {
			t.Fatalf("%q:\n got %s\nwant %s", value, got, want)
		}
	}
}

// The data goes as it came, except that a line break in its whitespace would end the line.
func TestDataWithALineBreakIsCompacted(t *testing.T) {
	row := sampleRow()
	row.Data = json.RawMessage("{\n  \"a\": 1,\r\n  \"b\": \"x\"\n}")

	if got := written(t, row); !bytes.Contains([]byte(got), []byte(`"dt":{"a":1,"b":"x"},`)) {
		t.Fatalf("got %s", got)
	}
}

func TestNoDataIsNull(t *testing.T) {
	row := sampleRow()
	row.Data = nil

	if got, want := written(t, row), encoded(t, row); got != want {
		t.Fatalf("got %s\nwant %s", got, want)
	}
}

func TestANumberJsonCannotHoldIsAnError(t *testing.T) {
	for _, number := range []float64{math.NaN(), math.Inf(1), math.Inf(-1)} {
		row := sampleRow()
		row.Cpu = float(number)

		if _, err := appendRow(nil, &row); err == nil {
			t.Fatalf("%v was written", number)
		}
	}
}
