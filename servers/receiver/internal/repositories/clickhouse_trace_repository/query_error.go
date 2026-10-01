package clickhouse_trace_repository

import (
	"regexp"
	"strconv"
)

// QueryError is an answer of ClickHouse other than 200, with the code it starts with.
type QueryError struct {
	Code    int
	Message string
}

func (e *QueryError) Error() string {
	return "clickhouse: " + e.Message
}

var codePattern = regexp.MustCompile(`^Code: (\d+)\.`)

// newQueryError reads the code from the start of the message only: the rest may quote data.
func newQueryError(message string) *QueryError {
	code := 0

	if match := codePattern.FindStringSubmatch(message); match != nil {
		code, _ = strconv.Atoi(match[1])
	}

	return &QueryError{Code: code, Message: message}
}
