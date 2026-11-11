//go:build integration

package clickhouse_trace_repository

import (
	"context"
	"encoding/json"
	"fmt"
	"os"
	"slogger_receiver/internal/modules/trace/entities"
	"strings"
	"testing"
	"time"
)

// Runs against a live ClickHouse with the traces table, for example:
//
//	CLICKHOUSE_URL=http://clickhouse:8123 CLICKHOUSE_DATABASE=slogger \
//	CLICKHOUSE_USERNAME=slogger CLICKHOUSE_PASSWORD=... go test -tags integration ./...
//
// It writes under a service id no installation has and deletes its rows afterwards.
func TestInsertTwiceAndReadTheLatestVersion(t *testing.T) {
	baseUrl := os.Getenv("CLICKHOUSE_URL")

	if baseUrl == "" {
		t.Skip("CLICKHOUSE_URL is not set")
	}

	repository := Get()

	ctx := context.Background()
	serviceId := 4_000_000_000 - int(time.Now().Unix()%1_000_000)

	defer func() {
		body, err := repository.conn.Send(ctx, map[string]string{"query": fmt.Sprintf("DELETE FROM traces WHERE sid = %d", serviceId)}, strings.NewReader(""), "")

		if err == nil {
			_ = body.Close()
		}
	}()

	loggedAt := time.Date(2026, 9, 29, 10, 0, 0, 123456000, time.UTC)
	duration := 0.2
	pid := uint32(4194304)

	row := func(status string, updatedAt time.Time) entities.Row {
		return entities.Row{
			ServiceId: serviceId,
			TraceId:   "it's-1",
			Type:      "request",
			Status:    status,
			Tags:      []string{"api"},
			Data:      json.RawMessage(`{"code":200}`),
			RawData:   `{"code":200}`,
			Duration:  &duration,
			Pid:       &pid,
			LoggedAt:  loggedAt.Format(entities.TimeLayout),
			CreatedAt: loggedAt.Format(entities.TimeLayout),
			UpdatedAt: updatedAt.Format(entities.TimeLayout),
		}
	}

	if err := repository.Insert(ctx, []entities.Row{row("started", loggedAt.Add(time.Second))}); err != nil {
		t.Fatal(err)
	}

	if err := repository.Insert(ctx, []entities.Row{row("success", loggedAt.Add(2*time.Second))}); err != nil {
		t.Fatal(err)
	}

	key := entities.Key{ServiceId: serviceId, LoggedAtMicro: loggedAt.UnixMicro(), TraceId: "it's-1"}

	found, err := repository.FindExisting(ctx, []entities.Key{key, {ServiceId: serviceId, LoggedAtMicro: loggedAt.UnixMicro(), TraceId: "absent"}})

	if err != nil {
		t.Fatal(err)
	}

	if len(found) != 1 {
		t.Fatalf("expected one trace, got %d", len(found))
	}

	stored := found[key]

	if stored.Status != "success" || stored.Type != "request" || *stored.Duration != 0.2 || stored.Pid == nil || *stored.Pid != pid || stored.RawData != `{"code":200}` {
		t.Fatalf("expected the latest version, got %+v", stored)
	}

	if len(stored.Tags) != 1 || stored.Tags[0] != "api" || stored.Memory != nil || !stored.CreatedAt.Equal(loggedAt) {
		t.Fatalf("unexpected fields %+v", stored)
	}
}
