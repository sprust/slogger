package datetime_helper

import (
	"log/slog"
	"time"

	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
)

func Now() primitive.DateTime {
	return primitive.NewDateTimeFromTime(time.Now().UTC())
}

func ConvertLoggedAt(loggedAt interface{}) primitive.DateTime {
	loggedAtDt, ok := loggedAt.(primitive.DateTime)

	if ok {
		return loggedAtDt
	}

	loggedAtString, ok := loggedAt.(string)

	if !ok {
		slog.Error("loggedAt is not a string: " + loggedAt.(string))

		return primitive.NewDateTimeFromTime(Now().Time().UTC())
	}

	t, err := time.Parse("2006-01-02T15:04:05.000Z", loggedAtString)

	if err != nil {
		t, err = time.Parse("2006-01-02 15:04:05.000", loggedAtString)

		if err != nil {
			slog.Error("failed to parse loggedAt: " + loggedAtString + ": " + err.Error())

			t = Now().Time()
		}
	}

	return primitive.NewDateTimeFromTime(t.UTC())
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
