package json_helper

import (
	"bytes"
	"encoding/json"
	"fmt"
	"sort"

	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
)

// Marshal writes a trace's data as JSON with its objects in the order they arrived.
//
// The data is decoded into bson.D and bson.A to keep that order (dto.Data), and
// json.Marshal turns a bson.D into an array of {Key, Value} pairs: the stored `dt_raw`
// would no longer be the object the client sent. Maps have no order to keep, so their
// keys are written sorted, which at least makes the output stable.
func Marshal(value interface{}) ([]byte, error) {
	buffer := &bytes.Buffer{}

	if err := write(buffer, value); err != nil {
		return nil, err
	}

	return buffer.Bytes(), nil
}

func write(buffer *bytes.Buffer, value interface{}) error {
	switch v := value.(type) {
	case bson.D:
		return writeDocument(buffer, v)
	case bson.A:
		return writeArray(buffer, v)
	case []interface{}:
		return writeArray(buffer, v)
	case bson.M:
		return writeMap(buffer, v)
	case map[string]interface{}:
		return writeMap(buffer, v)
	case primitive.DateTime:
		return writeScalar(buffer, v.Time().UTC())
	default:
		return writeScalar(buffer, v)
	}
}

func writeDocument(buffer *bytes.Buffer, document bson.D) error {
	buffer.WriteByte('{')

	for index, element := range document {
		if index > 0 {
			buffer.WriteByte(',')
		}

		if err := writeScalar(buffer, element.Key); err != nil {
			return err
		}

		buffer.WriteByte(':')

		if err := write(buffer, element.Value); err != nil {
			return err
		}
	}

	buffer.WriteByte('}')

	return nil
}

func writeMap(buffer *bytes.Buffer, values map[string]interface{}) error {
	keys := make([]string, 0, len(values))

	for key := range values {
		keys = append(keys, key)
	}

	sort.Strings(keys)

	document := make(bson.D, 0, len(keys))

	for _, key := range keys {
		document = append(document, bson.E{Key: key, Value: values[key]})
	}

	return writeDocument(buffer, document)
}

func writeArray(buffer *bytes.Buffer, items []interface{}) error {
	buffer.WriteByte('[')

	for index, item := range items {
		if index > 0 {
			buffer.WriteByte(',')
		}

		if err := write(buffer, item); err != nil {
			return err
		}
	}

	buffer.WriteByte(']')

	return nil
}

// writeScalar leaves <, > and & as they are: the text is data to show, not HTML to embed.
func writeScalar(buffer *bytes.Buffer, value interface{}) error {
	encoded := &bytes.Buffer{}

	encoder := json.NewEncoder(encoded)
	encoder.SetEscapeHTML(false)

	if err := encoder.Encode(value); err != nil {
		return fmt.Errorf("value %v: %w", value, err)
	}

	buffer.Write(bytes.TrimSuffix(encoded.Bytes(), []byte("\n")))

	return nil
}
