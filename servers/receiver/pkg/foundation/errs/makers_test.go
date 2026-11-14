package errs

import (
	"errors"
	"strings"
	"testing"
)

func TestTheTraceGrowsWithEachWrap(t *testing.T) {
	message := Err(Err(errors.New("boom"))).Error()

	if !strings.HasPrefix(message, "boom") || strings.Count(message, tracePrefix) != 1 || strings.Count(message, "\n - ") != 2 {
		t.Fatalf("unexpected message %q", message)
	}
}
