package errors

type TraceError interface {
	error
	GetInternalError() error
}

type moduleError struct {
	internal error
}

func Internal(err error) TraceError {
	return &moduleError{internal: err}
}

func (e *moduleError) GetInternalError() error {
	return e.internal
}

func (e *moduleError) Error() string {
	return "trace: internal error: " + e.internal.Error()
}

func (e *moduleError) Unwrap() error {
	return e.internal
}
