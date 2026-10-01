package main

import (
	"errors"
	"fmt"
	"math"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/helpers/bson_helper"
	"slogger_receiver/internal/helpers/datetime_helper"
	"slogger_receiver/internal/helpers/json_helper"
	"slogger_receiver/internal/repositories/clickhouse_trace_repository"
	"time"

	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
)

// unknownTraceType is what the receiver of MongoDB stored a trace under until its create came.
const unknownTraceType = "__UNKNOWN"

// toRow turns a trace of a tracesPeriodic collection into the row the receiver writes now.
func toRow(raw bson.Raw, doc bson.M, now time.Time) (clickhouse_trace_repository.Row, error) {
	serviceId, ok := asInt(doc["sid"])

	if !ok {
		return clickhouse_trace_repository.Row{}, errors.New("sid is missing or not a whole number")
	}

	traceId, ok := doc["tid"].(string)

	if !ok || traceId == "" {
		return clickhouse_trace_repository.Row{}, errors.New("tid is missing or empty")
	}

	traceType := asString(doc["tp"])

	if traceType == "" {
		traceType = unknownTraceType
	}

	data, err := readData(raw, doc)

	if err != nil {
		return clickhouse_trace_repository.Row{}, fmt.Errorf("dt: %w", err)
	}

	objectData := []byte("{}")

	if dto.IsObjectJson(data) {
		objectData = data
	}

	updatedAt := asTime(doc["uat"], now)

	return clickhouse_trace_repository.Row{
		ServiceId:     serviceId,
		TraceId:       traceId,
		ParentTraceId: asString(doc["ptid"]),
		Type:          traceType,
		Status:        asString(doc["st"]),
		Tags:          readTags(doc["tgs"]),
		Data:          objectData,
		RawData:       string(data),
		Duration:      asFloat(doc["dur"]),
		Memory:        asFloat(doc["mem"]),
		Cpu:           asFloat(doc["cpu"]),
		LoggedAt:      loggedAt(doc).Format(clickhouse_trace_repository.TimeLayout),
		CreatedAt:     asTime(doc["cat"], updatedAt).Format(clickhouse_trace_repository.TimeLayout),
		UpdatedAt:     updatedAt.Format(clickhouse_trace_repository.TimeLayout),
	}, nil
}

// loggedAt reads the moment the way the receiver does, from the raw text the client sent:
// `lat` itself kept milliseconds only, and the row's key needs the microseconds an update
// arriving later computes from its own copy of that text.
func loggedAt(doc bson.M) time.Time {
	for _, key := range []string{"rcLat", "ucLat"} {
		if raw, ok := doc[key].(string); ok && raw != "" {
			return datetime_helper.ConvertLoggedAt(raw)
		}
	}

	return datetime_helper.ConvertLoggedAt(doc["lat"])
}

// readData writes `dt` as JSON with its objects in their stored order, as the receiver
// reads a buffer document of the same BSON shape.
func readData(raw bson.Raw, doc bson.M) ([]byte, error) {
	value := bson_helper.OrderedValue(raw, "dt", doc["dt"])

	if value == nil {
		return []byte("[]"), nil
	}

	return json_helper.Marshal(value)
}

// readTags takes the names out of the stored `[{nm: …}]`.
func readTags(value interface{}) []string {
	items, ok := value.(primitive.A)

	if !ok {
		return []string{}
	}

	tags := make([]string, 0, len(items))

	for _, item := range items {
		switch tag := item.(type) {
		case string:
			tags = append(tags, tag)
		case bson.M:
			if name, ok := tag["nm"].(string); ok {
				tags = append(tags, name)
			}
		case bson.D:
			for _, element := range tag {
				if name, ok := element.Value.(string); ok && element.Key == "nm" {
					tags = append(tags, name)
				}
			}
		}
	}

	return tags
}

func asString(value interface{}) string {
	if text, ok := value.(string); ok {
		return text
	}

	return ""
}

func asInt(value interface{}) (int, bool) {
	switch number := value.(type) {
	case int32:
		return int(number), true
	case int64:
		return int(number), true
	case float64:
		if number != math.Trunc(number) {
			return 0, false
		}

		return int(number), true
	default:
		return 0, false
	}
}

func asFloat(value interface{}) *float64 {
	var number float64

	switch typed := value.(type) {
	case float64:
		number = typed
	case int32:
		number = float64(typed)
	case int64:
		number = float64(typed)
	default:
		return nil
	}

	return &number
}

func asTime(value interface{}, fallback time.Time) time.Time {
	if moment, ok := value.(primitive.DateTime); ok {
		return moment.Time().UTC().Truncate(time.Microsecond)
	}

	return fallback
}
