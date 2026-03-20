package dto

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
}

type ServiceTraces struct {
	items map[string]*Traces
}

func (t *ServiceTraces) Items() map[string]*Traces {
	return t.items
}

func (t *ServiceTraces) GetTraceIds() []string {
	ids := make([]string, 0, len(t.items))

	for traceId := range t.items {
		ids = append(ids, traceId)
	}

	return ids
}

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

func (t *ServiceTraces) Count() int {
	return len(t.items)
}
