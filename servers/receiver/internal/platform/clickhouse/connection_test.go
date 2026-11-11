package clickhouse

import (
	"context"
	"io"
	"net/http"
	"strings"
	"testing"
)

type roundTripFunc func(*http.Request) (*http.Response, error)

func (f roundTripFunc) RoundTrip(request *http.Request) (*http.Response, error) {
	return f(request)
}

func TestSendPreservesAuthenticationAndRequest(t *testing.T) {
	connection := New("http://clickhouse:8123/", "traces", "receiver", "test-password")
	type contextKey struct{}
	ctx := context.WithValue(context.Background(), contextKey{}, "request-context")
	connection.client.Transport = roundTripFunc(func(request *http.Request) (*http.Response, error) {
		if request.Header.Get("X-ClickHouse-User") != "receiver" ||
			request.Header.Get("X-ClickHouse-Key") != "test-password" {
			t.Fatal("ClickHouse authentication headers were lost")
		}
		if request.Method != http.MethodPost || request.URL.Query().Get("database") != "traces" ||
			request.URL.Query().Get("query") != "INSERT INTO traces FORMAT JSONEachRow" ||
			request.Header.Get("Content-Encoding") != "zstd" || request.Context().Value(contextKey{}) != "request-context" {
			t.Fatal("ClickHouse request contract changed")
		}
		body, err := io.ReadAll(request.Body)
		if err != nil || string(body) != "payload" {
			t.Fatalf("unexpected body: %q, %v", body, err)
		}
		return &http.Response{StatusCode: http.StatusOK, Body: io.NopCloser(strings.NewReader("ok"))}, nil
	})

	body, err := connection.Send(ctx, map[string]string{"query": "INSERT INTO traces FORMAT JSONEachRow"}, strings.NewReader("payload"), "zstd")
	if err != nil {
		t.Fatal(err)
	}
	defer body.Close()
}

func TestSendReturnsServerErrorMessage(t *testing.T) {
	connection := New("http://clickhouse:8123", "traces", "receiver", "test-password")
	connection.client.Transport = roundTripFunc(func(*http.Request) (*http.Response, error) {
		return &http.Response{
			StatusCode: http.StatusInternalServerError,
			Body:       io.NopCloser(strings.NewReader("Code: 241. DB::Exception: memory limit exceeded")),
		}, nil
	})

	_, err := connection.Send(context.Background(), nil, nil, "")
	if err == nil || err.Error() != "Code: 241. DB::Exception: memory limit exceeded" {
		t.Fatalf("server error message was lost: %v", err)
	}
}
