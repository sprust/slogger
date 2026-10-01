package main

import (
	"reflect"
	"strings"
	"testing"
	"time"

	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
)

var (
	loggedMs   = primitive.NewDateTimeFromTime(time.Date(2026, 9, 30, 10, 0, 0, 123000000, time.UTC))
	updatedMs  = primitive.NewDateTimeFromTime(time.Date(2026, 9, 30, 10, 0, 5, 456000000, time.UTC))
	createdMs  = primitive.NewDateTimeFromTime(time.Date(2026, 9, 30, 10, 0, 1, 0, time.UTC))
	migratedAt = time.Date(2026, 10, 1, 9, 0, 0, 0, time.UTC)
)

// periodicTrace is a trace as the receiver of MongoDB stored it.
func periodicTrace(extra ...bson.E) bson.D {
	doc := bson.D{
		{Key: "_id", Value: primitive.NewObjectID()},
		{Key: "sid", Value: int32(7)},
		{Key: "tid", Value: "trace-1"},
		{Key: "ptid", Value: nil},
		{Key: "tp", Value: "request"},
		{Key: "st", Value: "success"},
		{Key: "tgs", Value: bson.A{bson.D{{Key: "nm", Value: "/api"}}, bson.D{{Key: "nm", Value: "200"}}}},
		{Key: "dt", Value: bson.D{
			{Key: "uri", Value: "/api"},
			{Key: "query", Value: bson.D{}},
			{Key: "code", Value: float64(200)},
			{Key: "ratio", Value: 0.5},
			{Key: "items", Value: bson.A{bson.D{{Key: "z", Value: 1.0}, {Key: "a", Value: nil}}}},
		}},
		{Key: "dur", Value: 0.25},
		{Key: "mem", Value: nil},
		{Key: "cpu", Value: int32(12)},
		{Key: "lat", Value: loggedMs},
		{Key: "uat", Value: updatedMs},
		{Key: "cat", Value: createdMs},
	}

	return append(doc, extra...)
}

func convert(t *testing.T, doc bson.D) (raw bson.Raw, decoded bson.M) {
	t.Helper()

	raw, err := bson.Marshal(doc)

	if err != nil {
		t.Fatal(err)
	}

	if err := bson.Unmarshal(raw, &decoded); err != nil {
		t.Fatal(err)
	}

	return raw, decoded
}

func TestATraceBecomesTheRowTheReceiverWrites(t *testing.T) {
	raw, doc := convert(t, periodicTrace(bson.E{Key: "rcLat", Value: "2026-09-30 10:00:00.123456"}))

	row, err := toRow(raw, doc, migratedAt)

	if err != nil {
		t.Fatal(err)
	}

	data := `{"uri":"/api","query":{},"code":200,"ratio":0.5,"items":[{"z":1,"a":null}]}`

	if row.ServiceId != 7 || row.TraceId != "trace-1" || row.ParentTraceId != "" || row.Type != "request" || row.Status != "success" {
		t.Fatalf("fields: %+v", row)
	}

	if !reflect.DeepEqual(row.Tags, []string{"/api", "200"}) {
		t.Fatalf("tags: %v", row.Tags)
	}

	if row.RawData != data || string(row.Data) != data {
		t.Fatalf("data: %s / %s", row.RawData, row.Data)
	}

	if *row.Duration != 0.25 || row.Memory != nil || *row.Cpu != 12 {
		t.Fatalf("numbers: %v %v %v", row.Duration, row.Memory, row.Cpu)
	}

	// the microseconds come from the text the client sent, not from the stored milliseconds
	if row.LoggedAt != "2026-09-30 10:00:00.123456" || row.CreatedAt != "2026-09-30 10:00:01.000000" || row.UpdatedAt != "2026-09-30 10:00:05.456000" {
		t.Fatalf("moments: %s %s %s", row.LoggedAt, row.CreatedAt, row.UpdatedAt)
	}
}

func TestTheMomentComesFromTheUpdateWhenTheCreateLeftNone(t *testing.T) {
	raw, doc := convert(t, periodicTrace(bson.E{Key: "ucLat", Value: "2026-09-30T10:00:00.123456+02:00"}))

	row, err := toRow(raw, doc, migratedAt)

	if err != nil {
		t.Fatal(err)
	}

	if row.LoggedAt != "2026-09-30 08:00:00.123456" {
		t.Fatalf("logged at %s", row.LoggedAt)
	}
}

func TestTheStoredMomentIsTheLastResort(t *testing.T) {
	raw, doc := convert(t, periodicTrace())

	row, err := toRow(raw, doc, migratedAt)

	if err != nil {
		t.Fatal(err)
	}

	if row.LoggedAt != "2026-09-30 10:00:00.123000" {
		t.Fatalf("logged at %s", row.LoggedAt)
	}
}

func TestAnUpdateThatNeverMetItsCreateKeepsThePlaceholder(t *testing.T) {
	doc := periodicTrace()

	for index := range doc {
		switch doc[index].Key {
		case "tp", "cat":
			doc[index].Value = nil
		case "dt":
			doc[index].Value = bson.A{}
		}
	}

	raw, decoded := convert(t, doc)

	row, err := toRow(raw, decoded, migratedAt)

	if err != nil {
		t.Fatal(err)
	}

	if row.Type != unknownTraceType || row.CreatedAt != row.UpdatedAt || row.RawData != "[]" || string(row.Data) != "{}" {
		t.Fatalf("row %+v", row)
	}
}

func TestADocumentWithoutAServiceOrTraceIsRefused(t *testing.T) {
	for _, key := range []string{"sid", "tid"} {
		doc := periodicTrace()

		for index := range doc {
			if doc[index].Key == key {
				doc[index].Value = nil
			}
		}

		raw, decoded := convert(t, doc)

		if _, err := toRow(raw, decoded, migratedAt); err == nil {
			t.Fatalf("a document without %s was taken", key)
		}
	}
}

func TestTheProgressLineSaysWhereTheRunIs(t *testing.T) {
	p := progress{started: time.Now().Add(-10 * time.Second), collection: 3, collections: 72, planned: 14_800_000, moved: 60_000}

	line := p.line("traces_2026_09_30_19_20", 45000, 210000)

	if !strings.HasPrefix(line, "[3/72] traces_2026_09_30_19_20: 45000/210000, total 60.0K/14.8M, 6000/s, ~40m5") || !strings.HasSuffix(line, "s left") {
		t.Fatalf("line %q", line)
	}

	if short(950) != "950" || short(12_400) != "12.4K" || short(1_250_000) != "1.2M" {
		t.Fatalf("short: %s %s %s", short(950), short(12_400), short(1_250_000))
	}
}
