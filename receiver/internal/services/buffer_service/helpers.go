package buffer_service

import (
	"reflect"

	"go.mongodb.org/mongo-driver/bson/primitive"
)

func chooseTruthy(existing interface{}, fallback interface{}) interface{} {
	if isTruthy(existing) {
		return existing
	}

	return fallback
}

func chooseNonNil(existing interface{}, fallback interface{}) interface{} {
	if existing != nil {
		return existing
	}

	return fallback
}

func isTruthy(value interface{}) bool {
	if value == nil {
		return false
	}

	switch v := value.(type) {
	case bool:
		return v
	case string:
		return v != ""
	case int:
		return v != 0
	case int32:
		return v != 0
	case int64:
		return v != 0
	case float32:
		return v != 0
	case float64:
		return v != 0
	case primitive.DateTime:
		return v != 0
	}

	rv := reflect.ValueOf(value)

	switch rv.Kind() {
	case reflect.Slice, reflect.Array, reflect.Map:
		return rv.Len() > 0
	default:
		return true
	}
}
