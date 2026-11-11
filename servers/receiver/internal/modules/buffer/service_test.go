package buffer_test

import (
	"context"
	"encoding/json"
	"errors"
	"testing"

	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/modules/buffer"
	bufferErrors "slogger_receiver/internal/modules/buffer/errors"
	"slogger_receiver/pkg/foundation/errs"
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

func TestMalformedMessagesExposeBufferAndOriginalErrors(t *testing.T) {
	for _, message := range []*dto.TracesMessage{
		{Creating: "{"},
		{Updating: "{"},
	} {
		err := buffer.Get().Save(context.Background(), 1, message)
		if err == nil {
			t.Fatal("malformed message accepted")
		}

		wrapped := errs.Err(err)
		var bufferErr bufferErrors.BufferError
		var syntaxErr *json.SyntaxError
		if !errors.As(wrapped, &bufferErr) || !errors.As(wrapped, &syntaxErr) {
			t.Fatalf("error chain lost buffer or JSON error: %v", wrapped)
		}
		if bufferErr.GetInternalError() == nil {
			t.Fatal("internal error was lost")
		}
	}
}

func TestBufferErrorPreservesCancellation(t *testing.T) {
	err := errs.Err(bufferErrors.Internal(context.Canceled))
	if !errors.Is(err, context.Canceled) {
		t.Fatalf("cancellation was lost: %v", err)
	}
}
