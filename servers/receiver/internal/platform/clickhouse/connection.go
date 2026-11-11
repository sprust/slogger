package clickhouse

import (
	"context"
	"errors"
	"io"
	"net/http"
	"net/url"
	"os"
	"strings"
	"sync"
	"time"
)

const requestTimeout = 30 * time.Second

var once sync.Once
var instance *Connection

func Get() *Connection {
	once.Do(func() {
		instance = New(
			os.Getenv("CLICKHOUSE_URL"),
			os.Getenv("CLICKHOUSE_DATABASE"),
			os.Getenv("CLICKHOUSE_USERNAME"),
			os.Getenv("CLICKHOUSE_PASSWORD"),
		)
	})

	return instance
}

type Connection struct {
	baseUrl  string
	database string
	username string
	password string
	client   *http.Client
}

func New(baseUrl string, database string, username string, password string) *Connection {
	return &Connection{
		baseUrl:  strings.TrimRight(baseUrl, "/"),
		database: database,
		username: username,
		password: password,
		client:   &http.Client{Timeout: requestTimeout},
	}
}

func (r *Connection) Send(ctx context.Context, params map[string]string, body io.Reader, encoding string) (io.ReadCloser, error) {
	values := url.Values{}
	values.Set("database", r.database)

	for name, value := range params {
		values.Set(name, value)
	}

	request, err := http.NewRequestWithContext(ctx, http.MethodPost, r.baseUrl+"/?"+values.Encode(), body)

	if err != nil {
		return nil, err
	}

	request.Header.Set("X-ClickHouse-User", r.username)
	request.Header.Set("X-ClickHouse-Key", r.password)

	if encoding != "" {
		request.Header.Set("Content-Encoding", encoding)
	}

	response, err := r.client.Do(request)

	if err != nil {
		return nil, err
	}

	if response.StatusCode != http.StatusOK {
		message, _ := io.ReadAll(io.LimitReader(response.Body, 4096))

		_ = response.Body.Close()

		return nil, errors.New(strings.TrimSpace(string(message)))
	}

	return response.Body, nil
}
