package datetime_helper

import (
	"testing"
	"time"
)

func TestConvertLoggedAtParsesRFC3339Nano(t *testing.T) {
	loggedAt := "2026-03-20T05:12:22.07Z"

	got := ConvertLoggedAt(loggedAt)

	want := time.Date(2026, 3, 20, 5, 12, 22, 70_000_000, time.UTC)

	if !got.Equal(want) {
		t.Fatalf("ConvertLoggedAt(%q) = %v, want %v", loggedAt, got, want)
	}
}

func TestConvertLoggedAtParsesLegacyFormat(t *testing.T) {
	loggedAt := "2026-03-20 05:12:22.070"

	got := ConvertLoggedAt(loggedAt)

	want := time.Date(2026, 3, 20, 5, 12, 22, 70_000_000, time.UTC)

	if !got.Equal(want) {
		t.Fatalf("ConvertLoggedAt(%q) = %v, want %v", loggedAt, got, want)
	}
}

func TestConvertLoggedAtKeepsMicroseconds(t *testing.T) {
	loggedAt := "2026-09-29 10:00:00.123456789"

	got := ConvertLoggedAt(loggedAt)

	want := time.Date(2026, 9, 29, 10, 0, 0, 123_456_000, time.UTC)

	if !got.Equal(want) {
		t.Fatalf("ConvertLoggedAt(%q) = %v, want %v", loggedAt, got, want)
	}
}
