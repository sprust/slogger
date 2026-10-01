package dto

import (
	"bytes"
	"encoding/json"
	"unicode/utf8"
)

// Data is a trace's `dt` as the client sent it, byte for byte: nothing looks inside it, and
// the message it came in was validated as a whole.
type Data struct {
	Raw json.RawMessage
}

// UnmarshalJSON copies: encoding/json does not promise the bytes outlive the call. Invalid
// UTF-8, which can only sit inside a string, becomes U+FFFD: ClickHouse refuses the whole insert.
func (d *Data) UnmarshalJSON(data []byte) error {
	if !utf8.Valid(data) {
		d.Raw = bytes.ToValidUTF8(data, []byte("\uFFFD"))

		return nil
	}

	d.Raw = append(json.RawMessage(nil), data...)

	return nil
}

// IsNull says whether there is no data: the field was not sent, or was sent as null.
func (d Data) IsNull() bool {
	return IsNullJson(d.Raw)
}

// IsNullJson says whether raw holds no value: nothing at all, or null.
func IsNullJson(raw []byte) bool {
	trimmed := bytes.TrimSpace(raw)

	return len(trimmed) == 0 || bytes.Equal(trimmed, []byte("null"))
}

// IsEmptyJson says whether raw holds no data: no value, an empty array or an empty object.
func IsEmptyJson(raw []byte) bool {
	if IsNullJson(raw) {
		return true
	}

	trimmed := bytes.TrimSpace(raw)
	first, last := trimmed[0], trimmed[len(trimmed)-1]

	if !(first == '[' && last == ']') && !(first == '{' && last == '}') {
		return false
	}

	return len(bytes.TrimSpace(trimmed[1:len(trimmed)-1])) == 0
}

// IsObjectJson says whether raw holds a JSON object, the only shape the JSON column takes.
func IsObjectJson(raw []byte) bool {
	trimmed := bytes.TrimSpace(raw)

	return len(trimmed) > 0 && trimmed[0] == '{'
}
