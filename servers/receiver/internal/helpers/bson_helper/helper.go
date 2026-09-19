package bson_helper

import (
	"log/slog"

	"go.mongodb.org/mongo-driver/bson"
)

// OrderedValue reads a field out of the raw document with its field order intact.
//
// A document decoded into a bson.M carries its embedded documents as maps, so reading
// `dt` that way loses the order it was stored in and hands the next write a random one.
// Read from the raw bytes, an embedded document comes back as a bson.D.
//
// The fallback is what the caller already has from the decoded document — an unordered
// copy of the same value. Nothing is dropped when the raw bytes cannot be read; the field
// is only as good as it was before this existed.
func OrderedValue(raw bson.Raw, key string, fallback interface{}) interface{} {
	value, err := raw.LookupErr(key)

	if err != nil {
		return fallback
	}

	// Decoded from a copy: a cursor reuses its buffer for the next document, and the
	// driver hands binary values back as slices into it rather than copying them. The
	// decoded value outlives the loop that read it.
	value.Value = append([]byte(nil), value.Value...)

	var ordered interface{}

	if err := value.Unmarshal(&ordered); err != nil {
		slog.Error("failed to read " + key + " in order: " + err.Error())

		return fallback
	}

	return ordered
}
