package errors

type Error interface {
	error
	GetInternalError() error
}

type moduleError struct {
	internal error
}

func Internal(err error) Error {
	return &moduleError{internal: err}
}

func (e *moduleError) GetInternalError() error {
	return e.internal
}

func (e *moduleError) Error() string {
	return "trace: internal error: " + e.internal.Error()
}
