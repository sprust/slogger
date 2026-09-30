package clickhouse_trace_repository

import (
	"bytes"
	"encoding/json"
	"errors"
	"math"
	"strconv"
	"unicode/utf8"
)

// appendRow writes a row as one JSONEachRow line, as json.Encoder would with HTML escaping
// off, without re-validating Data: it is JSON already, and only a line break in it is cut.
func appendRow(buffer []byte, row *Row) ([]byte, error) {
	var err error

	buffer = append(buffer, `{"sid":`...)
	buffer = strconv.AppendInt(buffer, int64(row.ServiceId), 10)
	buffer = append(buffer, `,"tid":`...)
	buffer = appendString(buffer, row.TraceId)
	buffer = append(buffer, `,"ptid":`...)
	buffer = appendString(buffer, row.ParentTraceId)
	buffer = append(buffer, `,"tp":`...)
	buffer = appendString(buffer, row.Type)
	buffer = append(buffer, `,"st":`...)
	buffer = appendString(buffer, row.Status)
	buffer = append(buffer, `,"tgs":[`...)

	for index, tag := range row.Tags {
		if index > 0 {
			buffer = append(buffer, ',')
		}

		buffer = appendString(buffer, tag)
	}

	buffer = append(buffer, `],"dt":`...)

	if buffer, err = appendRaw(buffer, row.Data); err != nil {
		return nil, err
	}

	buffer = append(buffer, `,"dt_raw":`...)
	buffer = appendString(buffer, row.RawData)

	for _, number := range []struct {
		key   string
		value *float64
	}{{`,"dur":`, row.Duration}, {`,"mem":`, row.Memory}, {`,"cpu":`, row.Cpu}} {
		buffer = append(buffer, number.key...)

		if buffer, err = appendFloat(buffer, number.value); err != nil {
			return nil, err
		}
	}

	buffer = append(buffer, `,"lat":`...)
	buffer = appendString(buffer, row.LoggedAt)
	buffer = append(buffer, `,"cat":`...)
	buffer = appendString(buffer, row.CreatedAt)
	buffer = append(buffer, `,"uat":`...)
	buffer = appendString(buffer, row.UpdatedAt)

	return append(buffer, "}\n"...), nil
}

// appendRaw writes JSON as it is; whitespace with a line break in it would split the line.
func appendRaw(buffer []byte, raw json.RawMessage) ([]byte, error) {
	if len(raw) == 0 {
		return append(buffer, "null"...), nil
	}

	if bytes.IndexAny(raw, "\n\r") < 0 {
		return append(buffer, raw...), nil
	}

	compacted := bytes.NewBuffer(buffer)

	if err := json.Compact(compacted, raw); err != nil {
		return nil, err
	}

	return compacted.Bytes(), nil
}

// appendFloat writes a number the way encoding/json does, and null for no value.
func appendFloat(buffer []byte, value *float64) ([]byte, error) {
	if value == nil {
		return append(buffer, "null"...), nil
	}

	number := *value

	if math.IsNaN(number) || math.IsInf(number, 0) {
		return nil, errors.New("unsupported value: " + strconv.FormatFloat(number, 'g', -1, 64))
	}

	format := byte('f')

	if abs := math.Abs(number); abs != 0 && (abs < 1e-6 || abs >= 1e21) {
		format = 'e'
	}

	buffer = strconv.AppendFloat(buffer, number, format, -1, 64)

	// 1e-07 to 1e-7, as encoding/json does
	if format == 'e' {
		if length := len(buffer); length >= 4 && buffer[length-4] == 'e' && buffer[length-3] == '-' && buffer[length-2] == '0' {
			buffer[length-2] = buffer[length-1]
			buffer = buffer[:length-1]
		}
	}

	return buffer, nil
}

const hex = "0123456789abcdef"

// appendString writes a JSON string the way encoding/json does with HTML escaping off:
// invalid UTF-8 becomes U+FFFD, and U+2028 and U+2029 are escaped.
func appendString(buffer []byte, value string) []byte {
	buffer = append(buffer, '"')

	start := 0

	for index := 0; index < len(value); {
		if char := value[index]; char < utf8.RuneSelf {
			if char >= 0x20 && char != '"' && char != '\\' {
				index++

				continue
			}

			buffer = append(buffer, value[start:index]...)

			switch char {
			case '"', '\\':
				buffer = append(buffer, '\\', char)
			case '\n':
				buffer = append(buffer, '\\', 'n')
			case '\r':
				buffer = append(buffer, '\\', 'r')
			case '\t':
				buffer = append(buffer, '\\', 't')
			case '\b':
				buffer = append(buffer, '\\', 'b')
			case '\f':
				buffer = append(buffer, '\\', 'f')
			default:
				buffer = append(buffer, '\\', 'u', '0', '0', hex[char>>4], hex[char&0xF])
			}

			index++
			start = index

			continue
		}

		char, size := utf8.DecodeRuneInString(value[index:])

		if char == utf8.RuneError && size == 1 {
			buffer = append(buffer, value[start:index]...)
			buffer = append(buffer, '\\', 'u', 'f', 'f', 'f', 'd')
			index += size
			start = index

			continue
		}

		if char == '\u2028' || char == '\u2029' {
			buffer = append(buffer, value[start:index]...)
			buffer = append(buffer, '\\', 'u', '2', '0', '2', hex[char&0xF])
			index += size
			start = index

			continue
		}

		index += size
	}

	buffer = append(buffer, value[start:]...)

	return append(buffer, '"')
}
