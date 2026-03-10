package buffer_service

import (
	"log/slog"
	"time"

	"go.mongodb.org/mongo-driver/bson/primitive"
)

var loggedAtFormat = "2006-01-02 15:04:05.000"

func now() primitive.DateTime {
	return primitive.NewDateTimeFromTime(time.Now().UTC())
}

func convertLoggedAt(loggedAt string) primitive.DateTime {
	if t, err := time.ParseInLocation(loggedAtFormat, loggedAt, time.UTC); err == nil {
		return primitive.NewDateTimeFromTime(t)
	}

	slog.Error("failed to parse loggedAt: " + loggedAt)

	return now()
}
