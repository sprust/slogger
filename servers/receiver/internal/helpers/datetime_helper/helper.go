package datetime_helper

import (
	"log/slog"
	"time"

	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
)

var loggedAtFormat = "2006-01-02 15:04:05.000"

func Now() primitive.DateTime {
	return primitive.NewDateTimeFromTime(time.Now().UTC())
}

func ConvertLoggedAt(loggedAt string) primitive.DateTime {
	t, err := time.Parse(time.RFC3339, loggedAt)

	if err != nil {
		t, err = time.ParseInLocation(loggedAtFormat, loggedAt, time.UTC)
	}

	if err == nil {
		return primitive.NewDateTimeFromTime(t)
	}

	slog.Error("failed to parse loggedAt: " + loggedAt + ": " + err.Error())

	return Now()
}

func MakeTimestampsByLoggedAt(loggedAt primitive.DateTime) bson.M {
	t := loggedAt.Time().UTC().Truncate(time.Second)

	return bson.M{
		"s5":    primitive.NewDateTimeFromTime(sliceTime(t, 5*time.Second)),
		"s10":   primitive.NewDateTimeFromTime(sliceTime(t, 10*time.Second)),
		"s30":   primitive.NewDateTimeFromTime(sliceTime(t, 30*time.Second)),
		"min":   primitive.NewDateTimeFromTime(t.Truncate(time.Minute)),
		"min5":  primitive.NewDateTimeFromTime(sliceTime(t, 5*time.Minute)),
		"min10": primitive.NewDateTimeFromTime(sliceTime(t, 10*time.Minute)),
		"min30": primitive.NewDateTimeFromTime(sliceTime(t, 30*time.Minute)),
		"h":     primitive.NewDateTimeFromTime(t.Truncate(time.Hour)),
		"h4":    primitive.NewDateTimeFromTime(sliceTime(t, 4*time.Hour)),
		"h12":   primitive.NewDateTimeFromTime(sliceTime(t, 12*time.Hour)),
		"d":     primitive.NewDateTimeFromTime(startOfDay(t)),
		"m":     primitive.NewDateTimeFromTime(startOfMonth(t)),
	}
}

func sliceTime(t time.Time, step time.Duration) time.Time {
	return t.Add(-time.Duration(t.UnixNano()%step.Nanoseconds()) * time.Nanosecond)
}

func startOfDay(t time.Time) time.Time {
	y, m, d := t.Date()
	return time.Date(y, m, d, 0, 0, 0, 0, time.UTC)
}

func startOfMonth(t time.Time) time.Time {
	y, m, _ := t.Date()
	return time.Date(y, m, 1, 0, 0, 0, 0, time.UTC)
}
