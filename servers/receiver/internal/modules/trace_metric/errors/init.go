package errors

type TraceMetricError interface {
	error
	GetInternalError() error
}

type moduleError struct {
	internal error
}

func Internal(err error) TraceMetricError {
	return &moduleError{internal: err}
}

func (e *moduleError) GetInternalError() error {
	return e.internal
}

func (e *moduleError) Error() string {
	return "trace_metric: internal error: " + e.internal.Error()
}

func (e *moduleError) Unwrap() error {
	return e.internal
}
