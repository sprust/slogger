package errors

type BufferError interface {
	error
	GetInternalError() error
}

type bufferErrorImpl struct {
	internal error
}

func Internal(err error) BufferError {
	return &bufferErrorImpl{internal: err}
}

func (b *bufferErrorImpl) GetInternalError() error {
	return b.internal
}

func (b *bufferErrorImpl) Error() string {
	return "buffer: internal error: " + b.internal.Error()
}

func (b *bufferErrorImpl) Unwrap() error {
	return b.internal
}
