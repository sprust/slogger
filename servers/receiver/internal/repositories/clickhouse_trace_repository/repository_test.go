package clickhouse_trace_repository

import (
	"testing"
	"time"
)

func TestFormatKeysWritesTuplesOfTheSortingKey(t *testing.T) {
	loggedAt := time.Date(2026, 9, 29, 10, 0, 0, 123456000, time.UTC)

	got := formatKeys([]Key{
		{ServiceId: 1, LoggedAtMicro: loggedAt.UnixMicro(), TraceId: "plain"},
		{ServiceId: 22, LoggedAtMicro: loggedAt.UnixMicro(), TraceId: `it's a\b`},
	})

	want := `[(1,'2026-09-29 10:00:00.123456','plain'),(22,'2026-09-29 10:00:00.123456','it\'s a\\b')]`

	if got != want {
		t.Fatalf("got %s, want %s", got, want)
	}
}

func TestQuoteEscapesLineBreaks(t *testing.T) {
	if got := quote("a\nb\tc"); got != `'a\nb\tc'` {
		t.Fatalf("got %s", got)
	}
}
