package buffer_repository

import (
	"bytes"
	"errors"
	"testing"

	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
)

func TestReadBufferDocAcceptsACreatingDoc(t *testing.T) {
	id := primitive.NewObjectID()

	got, reason := readBufferDoc(bson.M{
		"_id": id,
		"op":  "c",
		"sid": int32(7),
		"tid": "trace-1",
	})

	if reason != "" {
		t.Fatalf("readBufferDoc rejected a valid doc: %s", reason)
	}

	want := bufferDoc{id: id, serviceId: 7, op: "c", traceId: "trace-1"}

	if got != want {
		t.Fatalf("readBufferDoc = %+v, want %+v", got, want)
	}
}

func TestReadBufferDocReadsAServiceIdOfAnyWidth(t *testing.T) {
	// The driver picks the width from the value, so the same field comes back as int32
	// today and int64 the day a service id gets large enough.
	for _, sid := range []interface{}{int32(7), int64(7), 7, float64(7)} {
		got, reason := readBufferDoc(bson.M{
			"_id": primitive.NewObjectID(),
			"op":  "u",
			"sid": sid,
			"tid": "trace-1",
		})

		if reason != "" {
			t.Fatalf("readBufferDoc rejected sid %T: %s", sid, reason)
		}

		if got.serviceId != 7 {
			t.Fatalf("readBufferDoc read sid %T as %d, want 7", sid, got.serviceId)
		}
	}
}

// The query no longer filters, so this is the only thing between a document of an
// unexpected shape and the transporter. It used to be a row of bare type assertions, which
// is to say a panic on the first such document — repeated on every pass, because a
// document nothing could act on was never removed either.
func TestReadBufferDocRejectsWhatCannotBeActedOn(t *testing.T) {
	cases := map[string]bson.M{
		"no op":            {"_id": primitive.NewObjectID(), "sid": int32(1), "tid": "t"},
		"unknown op":       {"_id": primitive.NewObjectID(), "op": "x", "sid": int32(1), "tid": "t"},
		"op not a string":  {"_id": primitive.NewObjectID(), "op": 1, "sid": int32(1), "tid": "t"},
		"no sid":           {"_id": primitive.NewObjectID(), "op": "c", "tid": "t"},
		"sid not a number": {"_id": primitive.NewObjectID(), "op": "c", "sid": "1", "tid": "t"},
		"fractional sid":   {"_id": primitive.NewObjectID(), "op": "c", "sid": 1.5, "tid": "t"},
		"no tid":           {"_id": primitive.NewObjectID(), "op": "c", "sid": int32(1)},
		"empty tid":        {"_id": primitive.NewObjectID(), "op": "c", "sid": int32(1), "tid": ""},
		// Readable, but not removable: everything downstream deletes by ObjectID, so this
		// one would be saved again on every pass and never leave the buffer.
		"_id not an ObjectID": {"_id": "not-an-object-id", "op": "c", "sid": int32(1), "tid": "t"},
	}

	for name, doc := range cases {
		t.Run(name, func(t *testing.T) {
			if _, reason := readBufferDoc(doc); reason == "" {
				t.Fatalf("readBufferDoc accepted %s", name)
			}
		})
	}
}

func TestUndecodableDocCopiesTheCursorBytes(t *testing.T) {
	id := primitive.NewObjectID()

	raw, err := bson.Marshal(bson.M{"_id": id, "op": "c"})

	if err != nil {
		t.Fatalf("bson.Marshal: %v", err)
	}

	got, ok := undecodableDoc(raw, errors.New("boom"))

	if !ok {
		t.Fatal("undecodableDoc refused a document that has an _id")
	}

	if got.Doc["_id"] != id {
		t.Fatalf("_id = %v, want %v", got.Doc["_id"], id)
	}

	if got.Reason != "decode failed" {
		// A fixed label: the reason is a map key in MoveToInvalid's log line, so the error
		// text belongs beside it, not in it.
		t.Fatalf("Reason = %q, want %q", got.Reason, "decode failed")
	}

	if got.Doc["err"] != "boom" {
		t.Fatalf("err = %v, want %q", got.Doc["err"], "boom")
	}

	binary, isBinary := got.Doc["raw"].(primitive.Binary)

	if !isBinary {
		t.Fatalf("raw is %T, want primitive.Binary", got.Doc["raw"])
	}

	// cursor.Current is only valid until the next Next(), and this entry is written well
	// after the loop has moved on. Overwriting the source stands in for the driver
	// handing that buffer back to its pool.
	before := append([]byte(nil), binary.Data...)

	for i := range raw {
		raw[i] = 0
	}

	if !bytes.Equal(binary.Data, before) {
		t.Fatal("undecodableDoc kept a reference to the cursor buffer instead of a copy")
	}
}

func TestUndecodableDocRefusesADocumentWithNoId(t *testing.T) {
	raw, err := bson.Marshal(bson.M{"op": "c"})

	if err != nil {
		t.Fatalf("bson.Marshal: %v", err)
	}

	// Nothing can address it, so nothing can move or delete it. Answering true would put a
	// copy in the invalid buffer on every pass and never take the original out.
	if _, ok := undecodableDoc(raw, errors.New("boom")); ok {
		t.Fatal("undecodableDoc accepted a document with no _id")
	}
}

func TestAsIntRejectsWhatIsNotAWholeNumber(t *testing.T) {
	for _, value := range []interface{}{"1", 1.5, nil, true, []byte("1")} {
		if got, ok := asInt(value); ok {
			t.Fatalf("asInt(%#v) = %d, want a refusal", value, got)
		}
	}
}

func TestFailedIndexesNamesTheDocumentsTheServerRefused(t *testing.T) {
	// A validation rule refused the middle one; the batch is unordered, so the other two
	// are in. Index is rebased onto the whole slice by the driver, even across batches.
	err := mongo.BulkWriteException{
		WriteErrors: []mongo.BulkWriteError{
			{WriteError: mongo.WriteError{Index: 1, Code: 121}},
		},
	}

	failed, perDocument := failedIndexes(err)

	if !perDocument {
		t.Fatal("failedIndexes could not tell which documents failed")
	}

	if len(failed) != 1 || !failed[1] {
		t.Fatalf("failed = %v, want only index 1", failed)
	}
}

func TestFailedIndexesRefusesWhenTheFailureIsNotPerDocument(t *testing.T) {
	// Nothing is known to have been written, so nothing may be deleted from the buffer.
	// ErrDocumentTooLarge belongs here too: the driver raises it before sending, as a
	// plain error, which is why undecodableDoc caps the bytes it keeps.
	cases := map[string]error{
		"not a bulk error": errors.New("connection refused"),
		"too large":        errors.New("an inserted document is too large"),
		"no write errors":  mongo.BulkWriteException{},
		"write concern error": mongo.BulkWriteException{
			WriteErrors:       []mongo.BulkWriteError{{WriteError: mongo.WriteError{Index: 0}}},
			WriteConcernError: &mongo.WriteConcernError{Message: "not enough nodes"},
		},
	}

	for name, err := range cases {
		t.Run(name, func(t *testing.T) {
			if _, perDocument := failedIndexes(err); perDocument {
				t.Fatalf("failedIndexes treated %s as per-document", name)
			}
		})
	}
}

func TestUndecodableDocCapsWhatItKeeps(t *testing.T) {
	id := primitive.NewObjectID()

	// A document larger than the cap. Keeping all of it is what would push the copy past
	// the 16MB limit, and the driver refuses that before sending — as a plain error, which
	// failedIndexes cannot narrow, so the whole batch would stay in the buffer.
	raw, err := bson.Marshal(bson.M{"_id": id, "dt": string(make([]byte, maxKeptRawBytes*2))})

	if err != nil {
		t.Fatalf("bson.Marshal: %v", err)
	}

	got, ok := undecodableDoc(raw, errors.New("boom"))

	if !ok {
		t.Fatal("undecodableDoc refused a document that has an _id")
	}

	binary := got.Doc["raw"].(primitive.Binary)

	if len(binary.Data) != maxKeptRawBytes {
		t.Fatalf("kept %d bytes, want %d", len(binary.Data), maxKeptRawBytes)
	}

	if got.Doc["rawLen"] != len(raw) {
		t.Fatalf("rawLen = %v, want %d", got.Doc["rawLen"], len(raw))
	}
}
