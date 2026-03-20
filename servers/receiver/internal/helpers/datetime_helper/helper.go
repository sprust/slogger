package datetime_helper

import (
	"fmt"
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

	if ok {
		layouts := []string{
			time.RFC3339Nano,
			time.RFC3339,
			"2006-01-02 15:04:05.999999999",
			"2006-01-02 15:04:05",
		}

		for _, layout := range layouts {
			t, err := time.Parse(layout, loggedAtString)

			if err == nil {
				return primitive.NewDateTimeFromTime(t.UTC())
			}
		}
	}

	slog.Error(fmt.Sprintf("failed to parse loggedAt: %v", loggedAtString))

	return primitive.NewDateTimeFromTime(Now().Time().UTC())
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
