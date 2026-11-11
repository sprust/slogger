package entities

import (
	"encoding/json"
	"time"
)

const TimeLayout = "2006-01-02 15:04:05.000000"

// Key is what a trace is found by: the sorting key of the table. The logged-at moment is
// in unix microseconds, the precision of the column, so that equal moments are equal keys.
type Key struct {
	ServiceId     int
	LoggedAtMicro int64
	TraceId       string
}

// StoredTrace is what is already stored of a trace — the other half of the merge.
type StoredTrace struct {
	ParentTraceId string
	Type          string
	Status        string
	Tags          []string
	RawData       string
	Duration      *float64
	Memory        *float64
	Cpu           *float64
	Pid           *uint32
	CreatedAt     time.Time
}

// Row is one trace as it is inserted.
type Row struct {
	ServiceId     int             `json:"sid"`
	TraceId       string          `json:"tid"`
	ParentTraceId string          `json:"ptid"`
	Type          string          `json:"tp"`
	Status        string          `json:"st"`
	Tags          []string        `json:"tgs"`
	Data          json.RawMessage `json:"dt"`
	RawData       string          `json:"dt_raw"`
	Duration      *float64        `json:"dur"`
	Memory        *float64        `json:"mem"`
	Cpu           *float64        `json:"cpu"`
	Pid           *uint32         `json:"pid"`
	LoggedAt      string          `json:"lat"`
	CreatedAt     string          `json:"cat"`
	UpdatedAt     string          `json:"uat"`
}
