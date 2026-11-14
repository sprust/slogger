package buffer_test

import (
	"context"
	"testing"

	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/modules/buffer"
	bufferErrors "slogger_receiver/internal/modules/buffer/internal/errors"
)

func TestEmptyOperationsReturnNilThroughErrorInterface(t *testing.T) {
	service := buffer.Get()
	ctx := context.Background()

	var err error = service.Save(ctx, 1, &dto.TracesMessage{Creating: "[]", Updating: "[]"})
	if err != nil {
		t.Fatalf("empty save returned a non-nil error: %v", err)
	}

	err = service.MoveToInvalid(ctx, nil)
	if err != nil {
		t.Fatalf("empty move returned a non-nil error: %v", err)
	}

	err = service.MarkFailed(ctx, nil, 5)
	if err != nil {
		t.Fatalf("empty mark returned a non-nil error: %v", err)
	}

	count, deleteErr := service.DeleteByIds(ctx, nil)
	err = deleteErr
	if count != 0 || err != nil {
		t.Fatalf("empty delete returned %d, %v", count, err)
	}
}

func TestMalformedMessagesReturnInternalErrors(t *testing.T) {
	for _, message := range []*dto.TracesMessage{
		{Creating: "{"},
		{Updating: "{"},
	} {
		err := buffer.Get().Save(context.Background(), 1, message)
		if err == nil {
			t.Fatal("malformed message accepted")
		}

		if err.GetInternalError() == nil {
			t.Fatal("internal error was lost")
		}
	}
}

func TestErrorPreservesCancellation(t *testing.T) {
	err := bufferErrors.Internal(context.Canceled)
	if err.GetInternalError() != context.Canceled {
		t.Fatalf("cancellation was lost: %v", err)
	}
}
