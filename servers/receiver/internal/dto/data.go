package dto

import (
	"bytes"
	"encoding/json"
	"errors"
	"fmt"
	"io"

	"go.mongodb.org/mongo-driver/bson"
)

// Data is a trace's `dt` payload, held in the order it arrived.
//
// json.Unmarshal into an interface{} turns a JSON object into a map, and a Go map has no
// order: the field order the client sent is gone before the trace reaches the buffer, and
// what MongoDB ends up storing is whatever order the map happened to iterate in. The
// tokens are read by hand instead, so an object becomes a bson.D and keeps its order all
// the way to the shard.
type Data struct {
	Value interface{}
}

func (d *Data) UnmarshalJSON(data []byte) error {
	decoder := json.NewDecoder(bytes.NewReader(data))

	value, err := decodeJsonValue(decoder)

	if err != nil {
		return err
	}

	// What json.Unmarshal does with trailing content, kept because a payload with two
	// values in it is a broken payload however it is read.
	if _, err := decoder.Token(); !errors.Is(err, io.EOF) {
		return errors.New("unexpected content after the data value")
	}

	d.Value = value

	return nil
}

func decodeJsonValue(decoder *json.Decoder) (interface{}, error) {
	token, err := decoder.Token()

	if err != nil {
		return nil, err
	}

	return decodeJsonToken(decoder, token)
}

// decodeJsonToken takes the token already read, because an array reads one to know
// whether the array has ended before it knows it is reading a value.
func decodeJsonToken(decoder *json.Decoder, token json.Token) (interface{}, error) {
	delimiter, ok := token.(json.Delim)

	// Numbers arrive as float64, which is what json.Unmarshal gave before this type
	// existed. Reading them as integers would change the BSON types under the dynamic
	// indexes and the data filters, and that is not what this is about.
	if !ok {
		return token, nil
	}

	switch delimiter {
	case '{':
		return decodeJsonObject(decoder)
	case '[':
		return decodeJsonArray(decoder)
	}

	return nil, fmt.Errorf("unexpected delimiter %q", delimiter)
}

func decodeJsonObject(decoder *json.Decoder) (bson.D, error) {
	document := bson.D{}

	for {
		token, err := decoder.Token()

		if err != nil {
			return nil, err
		}

		if delimiter, ok := token.(json.Delim); ok && delimiter == '}' {
			return document, nil
		}

		key, ok := token.(string)

		if !ok {
			return nil, fmt.Errorf("unexpected object key %v", token)
		}

		value, err := decodeJsonValue(decoder)

		if err != nil {
			return nil, err
		}

		document = setDocumentValue(document, key, value)
	}
}

func decodeJsonArray(decoder *json.Decoder) (bson.A, error) {
	array := bson.A{}

	for {
		token, err := decoder.Token()

		if err != nil {
			return nil, err
		}

		if delimiter, ok := token.(json.Delim); ok && delimiter == ']' {
			return array, nil
		}

		value, err := decodeJsonToken(decoder, token)

		if err != nil {
			return nil, err
		}

		array = append(array, value)
	}
}

// setDocumentValue writes a key the way the map did: the last value of a repeated key
// wins. Without it a bson.D would carry the key twice, which a map could never produce
// and nothing downstream expects.
//
// The scan is linear because the objects in trace data are small, and a second index per
// object would cost more than it saves.
func setDocumentValue(document bson.D, key string, value interface{}) bson.D {
	for index := range document {
		if document[index].Key == key {
			document[index].Value = value

			return document
		}
	}

	return append(document, bson.E{Key: key, Value: value})
}
