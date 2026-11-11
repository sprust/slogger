package errors

type ServiceError interface {
	error
	GetNotFoundToken() string
	GetInternalError() error
}

type serviceErrorImpl struct {
	notFoundByToken string
	internal        error
}

func NotFoundByToken(token string) ServiceError {
	return &serviceErrorImpl{notFoundByToken: token}
}

func Internal(err error) ServiceError {
	return &serviceErrorImpl{internal: err}
}

func (s *serviceErrorImpl) GetNotFoundToken() string {
	return s.notFoundByToken
}

func (s *serviceErrorImpl) GetInternalError() error {
	return s.internal
}

func (s *serviceErrorImpl) Error() string {
	if s.notFoundByToken != "" {
		return "service: not found by token " + s.notFoundByToken
	}

	if s.internal != nil {
		return "service: internal error: " + s.internal.Error()
	}

	return "service: unknown error"
}
