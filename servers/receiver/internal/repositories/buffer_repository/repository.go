package buffer_repository

import (
	"context"
	"errors"
	"fmt"
	"log/slog"
	"math"
	"os"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/helpers/datetime_helper"
	"slogger_receiver/pkg/foundation/errs"
	"sync"
	"time"

	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
)

// InvalidDoc is a buffer document the transporter could not act on, on its way out of the
// buffer. Reason is recorded beside it so the invalid buffer says why it is there —
// whether the format changed under us or a save kept failing.
type InvalidDoc struct {
	Doc    bson.M
	Reason string
}

// How much of an undecodable document's raw bytes is kept beside it.
//
// Enough to see what arrived, and far short of what would make the copy unwritable: the
// invalid buffer has the same 16MB document limit as any collection, and an insert over it
// is refused by the driver before it is sent — as a plain error, not a per-document one,
// so it would take the whole batch down with it. `rawLen` records what was really there.
const maxKeptRawBytes = 64 * 1024

// bufferDoc is what every buffer document has to carry to be worth acting on.
type bufferDoc struct {
	id        primitive.ObjectID
	serviceId int
	op        string
	traceId   string
}

var instance *Repository
var once sync.Once

func Get() *Repository {
	once.Do(func() {
		instance = &Repository{}
	})

	return instance
}

type Repository struct {
	mColl        *mongo.Collection
	mInvalidColl *mongo.Collection
	connectMutex sync.Mutex
}

func (r *Repository) InsertCreatingTraces(ctx context.Context, serviceId int, traces []dto.TraceCreating) error {
	if len(traces) == 0 {
		return nil
	}

	err := r.connect(ctx)

	if err != nil {
		return errs.Err(err)
	}

	docs := make([]interface{}, 0, len(traces))

	for _, trace := range traces {
		docs = append(docs, r.makeCreatingTraceDoc(serviceId, trace))
	}

	_, err = r.mColl.InsertMany(ctx, docs, options.InsertMany().SetOrdered(false))

	if err != nil {
		return errs.Err(err)
	}

	return nil
}

func (r *Repository) InsertUpdatingTraces(ctx context.Context, serviceId int, traces []dto.TraceUpdating) error {
	if len(traces) == 0 {
		return nil
	}

	err := r.connect(ctx)

	if err != nil {
		return errs.Err(err)
	}

	docs := make([]interface{}, 0, len(traces))

	for _, trace := range traces {
		docs = append(docs, r.makeUpdatingTraceDoc(serviceId, trace))
	}

	_, err = r.mColl.InsertMany(ctx, docs, options.InsertMany().SetOrdered(false))

	if err != nil {
		return errs.Err(err)
	}

	return nil
}

// FindMany reads a batch of the buffer, oldest first.
//
// Nothing is filtered out of the query. A document a filter skipped would never reach the
// transporter, so its id would never reach DeleteByIds or MarkFailed either — its attempt
// counter would not move, it would never reach the invalid buffer, and it would sit in the
// collection for good. Everything is read instead, and what cannot be acted on is returned
// as invalid so the caller can move it out on the same pass.
//
// That is the second return value: documents on their way to the invalid buffer, each
// with the reason it could not be used.
func (r *Repository) FindMany(ctx context.Context, limit int) (map[int]*dto.ServiceTraces, []InvalidDoc, error) {
	err := r.connect(ctx)

	if err != nil {
		return nil, nil, errs.Err(err)
	}

	cursor, err := r.mColl.Find(
		ctx,
		bson.M{},
		options.Find().
			SetLimit(int64(limit)).
			SetSort(bson.D{{Key: "cat", Value: 1}}),
	)

	if err != nil {
		return nil, nil, errs.Err(err)
	}

	defer func(cursor *mongo.Cursor, ctx context.Context) {
		err := cursor.Close(ctx)

		if err != nil {
			slog.Error(errs.Err(err).Error())
		}

	}(cursor, ctx)

	result := make(map[int]*dto.ServiceTraces)
	invalid := make([]InvalidDoc, 0)

	for cursor.Next(ctx) {
		var doc bson.M

		if err := cursor.Decode(&doc); err != nil {
			// The batch is not abandoned over one document: doing that would stop the
			// transporter for as long as that document is the oldest in the buffer, which
			// is for ever.
			slog.Error(errs.Err(err).Error())

			if undecodable, ok := undecodableDoc(cursor.Current, err); ok {
				invalid = append(invalid, undecodable)
			}

			continue
		}

		parsed, reason := readBufferDoc(doc)

		if reason != "" {
			invalid = append(invalid, InvalidDoc{Doc: doc, Reason: reason})

			continue
		}

		serviceId := parsed.serviceId

		if result[serviceId] == nil {
			result[serviceId] = &dto.ServiceTraces{}
		}

		if parsed.op == "c" {
			trace := &dto.TraceCreating{
				TraceId:  parsed.traceId,
				Type:     asString(doc["tp"]),
				Status:   asString(doc["st"]),
				Tags:     asInterfaces(doc["tgs"]),
				Data:     doc["dt"],
				LoggedAt: asLoggedAtString(doc["lat"]),
			}

			if receivedAt, ok := doc["cat"].(primitive.DateTime); ok {
				trace.ReceivedAt = receivedAt.Time().UTC()
			}

			if ptid, ok := doc["ptid"]; ok {
				ptidStr := asString(ptid)
				trace.ParentTraceId = &ptidStr
			}

			if dur, ok := doc["dur"]; ok {
				durFloat := asFloat64(dur)
				trace.Duration = &durFloat
			}

			if mem, ok := doc["mem"]; ok {
				memFloat := asFloat64(mem)
				trace.Memory = &memFloat
			}

			if cpu, ok := doc["cpu"]; ok {
				cpuFloat := asFloat64(cpu)
				trace.Cpu = &cpuFloat
			}

			result[serviceId].AddCreating(trace)

			result[serviceId].AddId(parsed.traceId, parsed.id)
		} else {
			trace := &dto.TraceUpdating{
				TraceId:        parsed.traceId,
				Status:         asString(doc["st"]),
				Data:           doc["dt"],
				ParentLoggedAt: doc["plat"],
			}

			if tgs, ok := doc["tgs"]; ok {
				tgsItems := asInterfaces(tgs)
				trace.Tags = &tgsItems
			}

			if dur, ok := doc["dur"]; ok {
				durFloat := asFloat64(dur)
				trace.Duration = &durFloat
			}

			if mem, ok := doc["mem"]; ok {
				memFloat := asFloat64(mem)
				trace.Memory = &memFloat
			}

			if cpu, ok := doc["cpu"]; ok {
				cpuFloat := asFloat64(cpu)
				trace.Cpu = &cpuFloat
			}

			result[serviceId].AddUpdating(trace)

			result[serviceId].AddId(parsed.traceId, parsed.id)
		}
	}

	if err := cursor.Err(); err != nil {
		return nil, nil, errs.Err(err)
	}

	return result, invalid, nil
}

func (r *Repository) DeleteByIds(ctx context.Context, ids []primitive.ObjectID) (int64, error) {
	if len(ids) == 0 {
		return 0, nil
	}

	err := r.connect(ctx)

	if err != nil {
		return 0, errs.Err(err)
	}

	many, err := r.mColl.DeleteMany(ctx, bson.M{"_id": bson.M{"$in": ids}})

	if err != nil {
		return 0, errs.Err(err)
	}

	return many.DeletedCount, nil
}

// MarkFailed increments the save-attempt counter for the given buffer documents
// and moves to the invalid-buffer collection those that reached maxAttempts.
func (r *Repository) MarkFailed(ctx context.Context, ids []primitive.ObjectID, maxAttempts int) error {
	if len(ids) == 0 {
		return nil
	}

	err := r.connect(ctx)

	if err != nil {
		return errs.Err(err)
	}

	_, err = r.mColl.UpdateMany(
		ctx,
		bson.M{"_id": bson.M{"$in": ids}},
		bson.M{"$inc": bson.M{"att": 1}},
	)

	if err != nil {
		return errs.Err(err)
	}

	cursor, err := r.mColl.Find(
		ctx,
		bson.M{
			"_id": bson.M{"$in": ids},
			"att": bson.M{"$gte": maxAttempts},
		},
	)

	if err != nil {
		return errs.Err(err)
	}

	defer func(cursor *mongo.Cursor, ctx context.Context) {
		if err := cursor.Close(ctx); err != nil {
			slog.Error(errs.Err(err).Error())
		}
	}(cursor, ctx)

	invalid := make([]InvalidDoc, 0)

	for cursor.Next(ctx) {
		var doc bson.M

		if err := cursor.Decode(&doc); err != nil {
			slog.Error(errs.Err(err).Error())

			continue
		}

		// The document's own count, which can be past the threshold: a pass whose
		// MoveToInvalid failed still incremented it on the way here.
		attempts, ok := asInt(doc["att"])

		if !ok {
			attempts = maxAttempts
		}

		invalid = append(invalid, InvalidDoc{
			Doc:    doc,
			Reason: fmt.Sprintf("save failed %d times", attempts),
		})
	}

	if err := cursor.Err(); err != nil {
		return errs.Err(err)
	}

	return r.MoveToInvalid(ctx, invalid)
}

// MoveToInvalid writes the given documents to the invalid-buffer collection, each stamped
// with the time and the reason, and removes them from the buffer.
//
// Inserted before deleted, and deliberately in that order: a failure in between leaves a
// copy in the invalid buffer that the next pass writes again, and a duplicate there is a
// smaller loss than a document removed without being kept anywhere.
//
// That is only true because the buffer's `_id` is not carried over — it moves to `bid`,
// and each copy gets an `_id` of its own. Keeping it would make the second write a
// duplicate-key error instead, which returns before the delete below and leaves the
// document in the buffer for good: the very thing this file exists to prevent.
//
// The documents handed in are stamped in place, `_id` among them, so this consumes the
// slice rather than reading it: calling it twice with the same one moves nothing.
func (r *Repository) MoveToInvalid(ctx context.Context, docs []InvalidDoc) error {
	if len(docs) == 0 {
		return nil
	}

	err := r.connect(ctx)

	if err != nil {
		return errs.Err(err)
	}

	invalidAt := datetime_helper.Now()

	invalidDocs := make([]interface{}, 0, len(docs))
	ids := make([]interface{}, 0, len(docs))
	accepted := make([]InvalidDoc, 0, len(docs))
	reasons := make(map[string]int, len(docs))

	for _, item := range docs {
		id, ok := item.Doc["_id"]

		if !ok {
			// There is nothing to delete it by, so moving it would copy it rather than
			// move it, once per pass, for ever.
			slog.Error("buffer document has no _id and cannot be moved to the invalid buffer")

			continue
		}

		delete(item.Doc, "_id")

		item.Doc["bid"] = id
		item.Doc["iat"] = invalidAt
		item.Doc["rsn"] = item.Reason

		invalidDocs = append(invalidDocs, item.Doc)
		ids = append(ids, id)
		accepted = append(accepted, item)
		reasons[item.Reason]++
	}

	// Everything handed in lacked an _id, each logged above. Nothing moved, and saying so
	// is the same answer the all-refused case gives below.
	if len(invalidDocs) == 0 {
		return errs.Err(errors.New("no document could be addressed to move it out of the buffer"))
	}

	if _, err := r.mInvalidColl.InsertMany(ctx, invalidDocs, options.InsertMany().SetOrdered(false)); err != nil {
		failed, perDocument := failedIndexes(err)

		// The failure says nothing about which documents it applies to, so none of them
		// can be assumed written and none may be deleted.
		if !perDocument {
			return errs.Err(err)
		}

		slog.Error(errs.Err(err).Error())

		// Narrowed together, so that the count and the reasons below still describe the
		// same documents — the ones that are about to leave the buffer.
		kept := make([]interface{}, 0, len(ids))

		reasons = make(map[string]int, len(ids))

		for index, id := range ids {
			if failed[index] {
				continue
			}

			kept = append(kept, id)
			reasons[accepted[index].Reason]++
		}

		ids = kept
	}

	// Every one of them refused. Reported as a failure rather than as nothing to do: the
	// caller asked for these to be moved, and they are all still in the buffer.
	if len(ids) == 0 {
		return errs.Err(errors.New("no document could be written to the invalid buffer"))
	}

	if _, err := r.mColl.DeleteMany(ctx, bson.M{"_id": bson.M{"$in": ids}}); err != nil {
		return errs.Err(err)
	}

	slog.Warn(fmt.Sprintf("moved %d docs to the invalid buffer: %v", len(ids), reasons))

	return nil
}

func (r *Repository) makeCreatingTraceDoc(serviceId int, trace dto.TraceCreating) bson.M {
	doc := bson.M{
		"op":  "c",
		"sid": serviceId,
		"tid": trace.TraceId,
		"tp":  trace.Type,
		"st":  trace.Status,
		"tgs": trace.Tags,
		"dt":  trace.Data,
		"lat": trace.LoggedAt,
		"cat": datetime_helper.Now(),
	}

	if trace.ParentTraceId != nil {
		doc["ptid"] = *trace.ParentTraceId
	}

	if trace.Duration != nil {
		doc["dur"] = *trace.Duration
	}

	if trace.Memory != nil {
		doc["mem"] = *trace.Memory
	}

	if trace.Cpu != nil {
		doc["cpu"] = *trace.Cpu
	}

	return doc
}

func (r *Repository) makeUpdatingTraceDoc(serviceId int, trace dto.TraceUpdating) bson.M {
	doc := bson.M{
		"op":   "u",
		"sid":  serviceId,
		"tid":  trace.TraceId,
		"st":   trace.Status,
		"dt":   trace.Data,
		"plat": trace.ParentLoggedAt,
		"cat":  datetime_helper.Now(),
	}

	if trace.Tags != nil {
		doc["tgs"] = *trace.Tags
	}

	if trace.Duration != nil {
		doc["dur"] = *trace.Duration
	}

	if trace.Memory != nil {
		doc["mem"] = *trace.Memory
	}

	if trace.Cpu != nil {
		doc["cpu"] = *trace.Cpu
	}

	return doc
}

func (r *Repository) connect(ctx context.Context) error {
	r.connectMutex.Lock()
	defer r.connectMutex.Unlock()

	if r.mColl != nil {
		return nil
	}

	url := fmt.Sprintf(
		"mongodb://%s:%s@%s:%s",
		os.Getenv("MONGODB_USERNAME"),
		os.Getenv("MONGODB_PASSWORD"),
		os.Getenv("MONGODB_HOST"),
		os.Getenv("MONGODB_PORT"),
	)

	opt := options.Client().ApplyURI(url)

	var err error

	client, err := mongo.Connect(ctx, opt)

	if err != nil {
		return errs.Err(err)
	}

	database := client.Database(os.Getenv("MONGODB_DB_TRACES"))

	r.mColl = database.Collection(os.Getenv("MONGODB_COLL_BUFFER"))
	r.mInvalidColl = database.Collection(os.Getenv("MONGODB_COLL_INVALID_BUFFER"))

	return nil
}

// readBufferDoc pulls out the fields the transporter cannot work without, and says why it
// could not when one of them is missing or of the wrong type.
//
// Every read is checked. These used to be bare type assertions, which held only while the
// receiver was the only writer and wrote exactly this shape — `op` was filtered on, but
// nothing covered the others, so a document with no `sid` panicked the transporter's loop
// whatever its `op` was. And it panicked again on the next start, on the same document.
func readBufferDoc(doc bson.M) (bufferDoc, string) {
	id, ok := doc["_id"].(primitive.ObjectID)

	if !ok {
		// Everything downstream addresses documents by ObjectID: the saved ones through
		// DeleteByIds, the failed ones through MarkFailed. One with any other kind of _id
		// can be read but never removed, so it would be saved again on every pass.
		return bufferDoc{}, "_id is not an ObjectID"
	}

	op, ok := doc["op"].(string)

	if !ok {
		return bufferDoc{}, "op is missing or not a string"
	}

	if op != "c" && op != "u" {
		// A label, not the value: the reason is a map key in MoveToInvalid's log line, and
		// the value is on the document that gets written anyway.
		return bufferDoc{}, "unknown op"
	}

	serviceId, ok := asInt(doc["sid"])

	if !ok {
		return bufferDoc{}, "sid is missing or not a whole number"
	}

	traceId, ok := doc["tid"].(string)

	if !ok {
		return bufferDoc{}, "tid is missing or not a string"
	}

	if traceId == "" {
		// Traces are grouped by this. An empty one would collect every document that has
		// no trace id into a single group and save them over one another.
		return bufferDoc{}, "tid is empty"
	}

	return bufferDoc{id: id, serviceId: serviceId, op: op, traceId: traceId}, ""
}

// undecodableDoc builds the invalid-buffer entry for a document that would not decode,
// carrying up to maxKeptRawBytes of its raw bytes and the true length beside them.
//
// Those bytes are a prefix of a document, not a document: reading a capped one back as
// BSON will not work, and is not meant to.
//
// Answers false when even the _id cannot be read. Such a document cannot be addressed and
// so cannot be moved or deleted; it stays in the buffer until the collection's `cat` TTL
// takes it, which is the one hole left in "everything leaves the buffer". MongoDB gives
// every document an _id, so reaching that is close to impossible — but it is not nothing,
// and it is better written down than discovered.
func undecodableDoc(raw bson.Raw, decodeErr error) (InvalidDoc, bool) {
	value, err := raw.LookupErr("_id")

	if err != nil {
		return InvalidDoc{}, false
	}

	// Decoded from a copy for the same reason the document is: a binary _id would
	// otherwise be handed back as a slice into cursor.Current, and this id outlives the
	// loop — MoveToInvalid both stores it and deletes by it.
	idValue := bson.RawValue{Type: value.Type, Value: append([]byte(nil), value.Value...)}

	var id interface{}

	if err := idValue.Unmarshal(&id); err != nil {
		return InvalidDoc{}, false
	}

	// Copied, not referenced: cursor.Current is only valid until the next Next(), and the
	// driver hands those buffers back to a pool. This one outlives the loop — it is
	// written by MoveToInvalid afterwards — so by then the bytes could be anything.
	//
	// Binary rather than bson.Raw: the driver encodes a Raw as a nested document, which
	// would both re-parse bytes that already failed to parse and store a second _id.
	kept := raw

	if len(kept) > maxKeptRawBytes {
		kept = kept[:maxKeptRawBytes]
	}

	return InvalidDoc{
		Doc: bson.M{
			"_id":    id,
			"raw":    primitive.Binary{Data: append([]byte(nil), kept...)},
			"rawLen": len(raw),
			"err":    decodeErr.Error(),
		},
		Reason: "decode failed",
	}, true
}

// failedIndexes answers which documents of a batch the server refused, when InsertMany
// reports failures for some of them — a failed validation rule, say, or a duplicate key on
// an index somebody added to the invalid buffer.
//
// Without it one such document strands every other document of an unordered batch in the
// buffer, and does so again on every later pass.
//
// Answers false when the error says nothing about individual documents. That covers the
// ordinary failures — no connection, a write concern that could not be met — and also a
// document the driver refuses client-side for exceeding the 16MB limit, which is why
// undecodableDoc caps what it keeps rather than relying on this.
func failedIndexes(err error) (map[int]bool, bool) {
	var bulk mongo.BulkWriteException

	if !errors.As(err, &bulk) || len(bulk.WriteErrors) == 0 {
		return nil, false
	}

	// A write concern error is about the batch, not about a document in it.
	if bulk.WriteConcernError != nil {
		return nil, false
	}

	failed := make(map[int]bool, len(bulk.WriteErrors))

	for _, writeError := range bulk.WriteErrors {
		failed[writeError.Index] = true
	}

	return failed, true
}

// asInt reads a whole number in whatever width bson handed it back. The driver picks the
// width from the value, so a service id is int32 today and int64 the day one gets large
// enough — a single assertion would work until exactly then.
func asInt(value interface{}) (int, bool) {
	switch v := value.(type) {
	case int:
		return v, true
	case int32:
		return int(v), true
	case int64:
		return int(v), true
	case float64:
		if v != math.Trunc(v) {
			return 0, false
		}

		return int(v), true
	default:
		return 0, false
	}
}

func asString(value interface{}) string {
	if value == nil {
		return ""
	}

	if str, ok := value.(string); ok {
		return str
	}

	return fmt.Sprint(value)
}

func asInterfaces(value interface{}) []interface{} {
	if value == nil {
		return []interface{}{}
	}

	if items, ok := value.([]interface{}); ok {
		return items
	}

	if items, ok := value.(primitive.A); ok {
		return []interface{}(items)
	}

	return []interface{}{value}
}

func asFloat64(value interface{}) float64 {
	switch v := value.(type) {
	case float64:
		return v
	case float32:
		return float64(v)
	case int:
		return float64(v)
	case int32:
		return float64(v)
	case int64:
		return float64(v)
	case primitive.Decimal128:
		f, exp, err := v.BigInt()
		if err != nil || f == nil {
			return 0
		}

		return float64(f.Int64()) * float64Pow10(exp)
	default:
		return 0
	}
}

func asLoggedAtString(value interface{}) string {
	switch v := value.(type) {
	case primitive.DateTime:
		return v.Time().UTC().Format(time.RFC3339Nano)
	case time.Time:
		return v.UTC().Format(time.RFC3339Nano)
	case string:
		return v
	default:
		return ""
	}
}

func float64Pow10(exp int) float64 {
	result := 1.0

	if exp > 0 {
		for i := 0; i < exp; i++ {
			result *= 10
		}

		return result
	}

	for i := 0; i < -exp; i++ {
		result /= 10
	}

	return result
}
