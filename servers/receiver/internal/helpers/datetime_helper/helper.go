package datetime_helper

import (
	"fmt"
	"log/slog"
	"time"

	"go.mongodb.org/mongo-driver/bson/primitive"
)

func Now() primitive.DateTime {
	return primitive.NewDateTimeFromTime(time.Now().UTC())
}

// ConvertLoggedAt reads a trace's logged-at moment in UTC, at the microsecond precision
// of the traces table. The precision is part of the trace's key there: a create and its
// update meet only if both round the same moment the same way.
func ConvertLoggedAt(loggedAt interface{}) time.Time {
	if loggedAtDt, ok := loggedAt.(primitive.DateTime); ok {
		return loggedAtDt.Time().UTC().Truncate(time.Microsecond)
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
				return t.UTC().Truncate(time.Microsecond)
			}
		}
	}

	slog.Error(fmt.Sprintf("failed to parse loggedAt: %v", loggedAtString))

	return time.Now().UTC().Truncate(time.Microsecond)
}
