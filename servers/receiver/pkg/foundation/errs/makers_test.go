package errs

import (
	"errors"
	"io"
	"strings"
	"testing"
)

type codeError struct {
	code int
}

func (e *codeError) Error() string {
	return "code"
}

func TestTheWrappedErrorCanStillBeFound(t *testing.T) {
	err := Err(Err(&codeError{code: 241}))

	var found *codeError

	if !errors.As(err, &found) || found.code != 241 {
		t.Fatalf("the wrapped error was lost: %v", err)
	}

	if !errors.Is(Err(io.EOF), io.EOF) {
		t.Fatal("io.EOF was lost")
	}
}

func TestTheTraceGrowsWithEachWrap(t *testing.T) {
	message := Err(Err(errors.New("boom"))).Error()

	if !strings.HasPrefix(message, "boom") || strings.Count(message, tracePrefix) != 1 || strings.Count(message, "\n - ") != 2 {
		t.Fatalf("unexpected message %q", message)
	}
}
