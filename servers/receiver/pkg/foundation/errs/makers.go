package errs

import (
	"runtime"
	"strconv"
	"strings"
)

var tracePrefix = "\n->stack trace:"
var traceSuffix = "\n<-end of trace"

func Err(err error) error {
	if err == nil {
		return nil
	}

	msg := err.Error()

	startTraceIndex := strings.Index(msg, tracePrefix)
	endTraceIndex := strings.Index(msg, traceSuffix)

	if startTraceIndex == -1 || endTraceIndex == -1 {
		msg += tracePrefix + getCaller() + traceSuffix
	} else {
		traceText := msg[startTraceIndex:endTraceIndex]

		cleanMsg := msg[:startTraceIndex] + msg[endTraceIndex+len(traceSuffix):]

		msg = cleanMsg + traceText + getCaller() + traceSuffix
	}

	return &tracedError{message: msg, cause: err}
}

// tracedError carries the stack trace in its text and keeps the error it wraps for errors.As.
type tracedError struct {
	message string
	cause   error
}

func (e *tracedError) Error() string {
	return e.message
}

func (e *tracedError) Unwrap() error {
	return e.cause
}

func getCaller() string {
	_, file, line, ok := runtime.Caller(2)

	if !ok {
		return "unknown"
	}

	return "\n - " + file + ":" + strconv.Itoa(line)
}
