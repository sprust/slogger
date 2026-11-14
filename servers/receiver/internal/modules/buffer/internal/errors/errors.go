package errors

type Error interface {
	error
	GetInternalError() error
}

type bufferErrorImpl struct {
	internal error
}

func Internal(err error) Error {
	return &bufferErrorImpl{internal: err}
}

func (b *bufferErrorImpl) GetInternalError() error {
	return b.internal
}

func (b *bufferErrorImpl) Error() string {
	return "buffer: internal error: " + b.internal.Error()
}
