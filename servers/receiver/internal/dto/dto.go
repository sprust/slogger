package dto

import (
	"time"

	"go.mongodb.org/mongo-driver/bson/primitive"
)

type AuthMessage struct {
	ApiToken string `json:"t"`
}

type TracesMessage struct {
	Creating string `json:"c,omitempty"`
	Updating string `json:"u,omitempty"`
}

type TraceCreating struct {
	TraceId       string        `json:"tid"`
	ParentTraceId *string       `json:"ptid,omitempty"`
	Type          string        `json:"tp"`
	Status        string        `json:"st"`
	Tags          []interface{} `json:"tgs"`
	Data          interface{}   `json:"dt"`
	Duration      *float64      `json:"dur,omitempty"`
	Memory        *float64      `json:"mem,omitempty"`
	Cpu           *float64      `json:"cpu,omitempty"`
	LoggedAt      interface{}   `json:"lat"`
	// ReceivedAt is when the buffer took the trace in. Not part of the message: it is
	// read back from the buffer document, which is the only place that knows it.
	ReceivedAt time.Time `json:"-"`
}

type TraceUpdating struct {
	TraceId        string         `json:"tid"`
	Status         string         `json:"st"`
	Tags           *[]interface{} `json:"tgs,omitempty"`
	Data           interface{}    `json:"dt"`
	Duration       *float64       `json:"dur,omitempty"`
	Memory         *float64       `json:"mem,omitempty"`
	Cpu            *float64       `json:"cpu,omitempty"`
	ParentLoggedAt interface{}    `json:"plat"`
}

type Traces struct {
	Creating *TraceCreating
	Updating *TraceUpdating
	Ids      []primitive.ObjectID
}

type ServiceTraces struct {
	items map[string]*Traces
}

func (t *ServiceTraces) Items() map[string]*Traces {
	return t.items
}

// AddCreating files a trace under its id, replacing whatever was filed there before.
//
// The replacing is deliberate, not a merge: two creating documents with the same trace id
// are taken to mean the client sent the same trace twice, so the earlier payload is
// dropped rather than reconciled. That is an assumption about clients, not a property of
// the data — if one ever sends two different traces under one id, one of them is lost
// here. The caller adds both documents' ids either way, so both leave the buffer.
func (t *ServiceTraces) AddCreating(trace *TraceCreating) {
	if t.items == nil {
		t.items = make(map[string]*Traces)
	}

	items := t.items[trace.TraceId]

	if items == nil {
		t.items[trace.TraceId] = &Traces{}
	}

	t.items[trace.TraceId].Creating = trace
}

// AddUpdating files an update under its trace id. Collapsed like AddCreating, and for
// the same reason.
func (t *ServiceTraces) AddUpdating(trace *TraceUpdating) {
	if t.items == nil {
		t.items = make(map[string]*Traces)
	}

	items := t.items[trace.TraceId]

	if items == nil {
		t.items[trace.TraceId] = &Traces{}
	}

	t.items[trace.TraceId].Updating = trace
}

func (t *ServiceTraces) AddId(traceId string, id primitive.ObjectID) {
	if t.items == nil {
		t.items = make(map[string]*Traces)
	}

	if t.items[traceId] == nil {
		t.items[traceId] = &Traces{}
	}

	t.items[traceId].Ids = append(t.items[traceId].Ids, id)
}

func (t *ServiceTraces) Count() int {
	return len(t.items)
}
