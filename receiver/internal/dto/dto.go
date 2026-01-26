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
	IsParent      *bool         `json:"isp,omitempty"`
	LoggedAt      float64       `json:"lat"`
}

type TraceUpdating struct {
	TraceId  string         `json:"tid"`
	Status   string         `json:"st"`
	Tags     *[]interface{} `json:"tgs,omitempty"`
	Data     interface{}    `json:"dt"`
	Duration *float64       `json:"dur,omitempty"`
	Memory   *float64       `json:"mem,omitempty"`
	Cpu      *float64       `json:"cpu,omitempty"`
}
