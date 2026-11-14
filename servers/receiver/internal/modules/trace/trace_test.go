package trace_test

import (
	"context"
	"testing"

	"slogger_receiver/internal/modules/trace"
)

func TestEmptyBatchReturnsNilThroughErrorInterface(t *testing.T) {
	service := trace.Get()
	result, traceErr := service.Save(context.Background(), nil)
	var err error = traceErr
	if err != nil || result.Saved != 0 || len(result.Failed) != 0 {
		t.Fatalf("empty batch returned %+v, %v", result, err)
	}

}
