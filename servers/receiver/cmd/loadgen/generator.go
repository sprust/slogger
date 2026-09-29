package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"math"
	"math/rand/v2"
	"strings"
	"time"
)

type traceCreating struct {
	TraceId       string   `json:"tid"`
	ParentTraceId *string  `json:"ptid,omitempty"`
	Type          string   `json:"tp"`
	Status        string   `json:"st"`
	Tags          []string `json:"tgs,omitempty"`
	Data          any      `json:"dt"`
	Duration      *float64 `json:"dur,omitempty"`
	Memory        *float64 `json:"mem,omitempty"`
	Cpu           *float64 `json:"cpu,omitempty"`
	LoggedAt      string   `json:"lat"`
}

type traceUpdating struct {
	TraceId        string    `json:"tid"`
	Status         string    `json:"st"`
	Tags           *[]string `json:"tgs,omitempty"`
	Data           any       `json:"dt,omitempty"`
	Duration       *float64  `json:"dur,omitempty"`
	Memory         *float64  `json:"mem,omitempty"`
	Cpu            *float64  `json:"cpu,omitempty"`
	ParentLoggedAt string    `json:"plat"`
}

// obj is a JSON object that keeps its keys in the order they were written, as a PHP
// array does: the receiver stores `dt` in the order it arrives.
type obj []kv

type kv struct {
	k string
	v any
}

func (o obj) MarshalJSON() ([]byte, error) {
	var buffer bytes.Buffer

	buffer.WriteByte('{')

	for i, pair := range o {
		if i > 0 {
			buffer.WriteByte(',')
		}

		key, _ := json.Marshal(pair.k)
		value, err := json.Marshal(pair.v)

		if err != nil {
			return nil, err
		}

		buffer.Write(key)
		buffer.WriteByte(':')
		buffer.Write(value)
	}

	buffer.WriteByte('}')

	return buffer.Bytes(), nil
}

// tree is one root trace with its children: what one request, job or command sends.
type tree struct {
	creating []traceCreating
	updating []traceUpdating
}

type generator struct {
	rnd       *rand.Rand
	tidPrefix string
	pid       int
	bigDtRate float64
}

func newGenerator(seed uint64, tidPrefix string, bigDtRate float64) *generator {
	rnd := rand.New(rand.NewPCG(seed, seed^0x9e3779b97f4a7c15))

	return &generator{
		rnd:       rnd,
		tidPrefix: tidPrefix,
		pid:       20 + rnd.IntN(600),
		bigDtRate: bigDtRate,
	}
}

func formatLoggedAt(t time.Time) string {
	return t.UTC().Format("2006-01-02T15:04:05.000-07:00")
}

func (g *generator) tid() string {
	b := make([]byte, 16)

	for i := range b {
		b[i] = byte(g.rnd.UintN(256))
	}

	b[6] = (b[6] & 0x0f) | 0x40
	b[8] = (b[8] & 0x3f) | 0x80

	return fmt.Sprintf("%s-%x-%x-%x-%x-%x", g.tidPrefix, b[0:4], b[4:6], b[6:8], b[8:10], b[10:16])
}

// logNormal gives the long right tail real durations have.
func (g *generator) logNormal(median float64, sigma float64) float64 {
	return median * math.Exp(sigma*g.rnd.NormFloat64())
}

func (g *generator) seconds(medianMs float64) *float64 {
	value := math.Round(g.logNormal(medianMs, 0.8)*1000) / 1e6

	return &value
}

func (g *generator) memory() *float64 {
	value := math.Round((6+g.rnd.Float64()*40)*100) / 100

	return &value
}

func (g *generator) cpu() *float64 {
	value := math.Round((5+g.rnd.Float64()*85)*100) / 100

	return &value
}

func (g *generator) chance(p float64) bool {
	return g.rnd.Float64() < p
}

func (g *generator) between(from int, to int) int {
	return from + g.rnd.IntN(to-from+1)
}

func pick[T any](g *generator, items []T) T {
	return items[g.rnd.IntN(len(items))]
}

func (g *generator) fill(pattern string) string {
	if strings.Contains(pattern, "%d") {
		return fmt.Sprintf(pattern, g.between(1, 250000))
	}

	if strings.Contains(pattern, "%x") {
		return fmt.Sprintf(pattern, g.rnd.Uint32())
	}

	return pattern
}

func (g *generator) complement(data obj, classes ...string) obj {
	stack := make([]any, 0, len(classes))

	for _, class := range classes {
		stack = append(stack, obj{{"class", class}, {"line", g.between(12, 420)}})
	}

	return append(data, kv{"__trace", stack}, kv{"__add", obj{{"__context", obj{{"pid", g.pid}}}}})
}

func (g *generator) ip() string {
	return fmt.Sprintf("%d.%d.%d.%d", g.between(10, 223), g.rnd.IntN(256), g.rnd.IntN(256), g.between(1, 254))
}

// generate builds one tree of the given root type logged at lat.
func (g *generator) generate(rootType string, lat time.Time) tree {
	switch rootType {
	case "request":
		return g.request(lat)
	case "job":
		return g.job(lat)
	case "command":
		return g.command(lat)
	default:
		return g.task(lat)
	}
}

func (g *generator) request(lat time.Time) tree {
	r := pick(g, routes)
	uri := g.fill(r.uri)
	tid := g.tid()
	failed := g.chance(r.failedRate)

	params := obj{}

	if r.withItems {
		items := make([]any, 0, 4)

		for range g.between(1, 5) {
			items = append(items, obj{
				{"sku", pick(g, skus)},
				{"qty", g.between(1, 5)},
				{"price", math.Round(g.rnd.Float64()*20000) / 100},
			})
		}

		params = append(params, kv{"items", items}, kv{"currency", pick(g, []string{"EUR", "USD", "CHF"})})
	} else if r.method == "GET" {
		params = append(params, kv{"page", g.between(1, 20)}, kv{"per_page", 20})
	}

	headers := obj{
		{"host", "shop.example.test"},
		{"x-real-ip", g.ip()},
		{"x-forwarded-proto", "https"},
		{"connection", "close"},
		{"authorization", "********"},
		{"user-agent", pick(g, userAgents)},
		{"accept", "application/json"},
		{"content-type", "application/json"},
		{"accept-encoding", "gzip, deflate, br"},
		{"accept-language", pick(g, []string{"de-CH,de;q=0.9", "en-US,en;q=0.9", "fr-CH,fr;q=0.9"})},
	}

	common := obj{
		{"ip_address", g.ip()},
		{"uri", uri},
		{"method", r.method},
		{"action", r.action},
		{"middlewares", []string{"App\\Http\\Middleware\\Authenticate", "Illuminate\\Routing\\Middleware\\ThrottleRequests:api", "SLoggerLaravel\\Middleware\\HttpMiddleware"}},
		{"query", obj{}},
		{"query_string", nil},
		{"route_parameters", obj{}},
	}

	startData := append(append(obj{}, common...), kv{"boot_time", -1}, kv{"request", obj{{"headers", headers}, {"parameters", params}}})

	status := 200
	st := "success"

	if failed {
		status = pick(g, []int{422, 500, 500, 502, 503})
		st = "failed"
	} else if r.method == "POST" {
		status = 201
	}

	responseData := obj{{"__cleaned", nil}}

	if r.withItems && !failed {
		responseData = obj{{"order", obj{{"id", g.between(1, 900000)}, {"total", math.Round(g.rnd.Float64()*90000) / 100}, {"status", "created"}}}}
	}

	if failed && status == 500 {
		responseData = obj{{"message", "Server Error"}, {"exception", "Illuminate\\Database\\QueryException"}}
	}

	if g.chance(g.bigDtRate) {
		responseData = obj{{"__cleaned", strings.Repeat("x", 100*1024)}}
	}

	endData := append(append(obj{}, startData...), kv{"response", obj{
		{"status", status},
		{"headers", obj{{"cache-control", "no-cache, private"}, {"content-type", "application/json"}, {"x-parent-trace-id", tid}}},
		{"data", responseData},
	}})

	root := traceCreating{
		TraceId:  tid,
		Type:     "request",
		Status:   "started",
		Tags:     []string{r.pattern},
		Data:     g.complement(startData),
		LoggedAt: formatLoggedAt(lat),
	}

	duration := g.seconds(r.medianMs)

	endTags := []string{r.pattern, fmt.Sprintf("%d", status)}

	result := tree{creating: []traceCreating{root}}

	result.creating = append(result.creating, g.requestChildren(tid, lat, *duration, failed)...)
	result.updating = []traceUpdating{{
		TraceId:        tid,
		Status:         st,
		Tags:           &endTags,
		Data:           g.complement(endData),
		Duration:       duration,
		Memory:         g.memory(),
		Cpu:            g.cpu(),
		ParentLoggedAt: formatLoggedAt(lat),
	}}

	return result
}

// requestChildren is what a request does in between: queries first of all, the rest less often.
func (g *generator) requestChildren(parent string, lat time.Time, duration float64, failed bool) []traceCreating {
	children := make([]traceCreating, 0, 24)

	at := func() time.Time {
		return lat.Add(time.Duration(g.rnd.Float64() * duration * float64(time.Second)))
	}

	for range g.between(3, 15) {
		children = append(children, g.database(parent, at()))
	}

	for range g.between(1, 5) {
		children = append(children, g.cache(parent, at()))
	}

	for range g.between(1, 4) {
		children = append(children, g.event(parent, at()))
	}

	for range g.between(0, 3) {
		children = append(children, g.model(parent, at()))
	}

	for range g.between(0, 2) {
		children = append(children, g.gate(parent, at()))
	}

	for range g.between(0, 1) {
		children = append(children, g.log(parent, at(), failed))
	}

	if failed {
		children = append(children, g.log(parent, at(), true))
	}

	if g.chance(0.2) {
		children = append(children, g.http(parent, at()))
	}

	if g.chance(0.03) {
		children = append(children, g.mail(parent, at()))
	}

	if g.chance(0.03) {
		children = append(children, g.notification(parent, at()))
	}

	if g.chance(0.01) {
		children = append(children, g.dump(parent, at()))
	}

	return children
}

func (g *generator) child(parent string, tp string, st string, tags []string, data obj, duration *float64, lat time.Time) traceCreating {
	return traceCreating{
		TraceId:       g.tid(),
		ParentTraceId: &parent,
		Type:          tp,
		Status:        st,
		Tags:          tags,
		Data:          data,
		Duration:      duration,
		Memory:        g.memory(),
		Cpu:           g.cpu(),
		LoggedAt:      formatLoggedAt(lat),
	}
}

func (g *generator) database(parent string, lat time.Time) traceCreating {
	q := pick(g, queries)
	bindings := make([]any, 0, q.bindings)

	for range q.bindings {
		bindings = append(bindings, g.between(1, 100000))
	}

	tags := []string{pick(g, connections), q.sql}

	if len(tags[1]) > 40 {
		tags[1] = tags[1][:40]
	}

	data := g.complement(obj{
		{"connection", tags[0]},
		{"sql", q.sql},
		{"bindings", bindings},
	}, "Illuminate\\Database\\Eloquent\\Builder", "App\\Repositories\\"+strings.ToUpper(q.tables[:1])+q.tables[1:]+"Repository")

	return g.child(parent, "database", "success", tags, data, g.seconds(q.medianMs), lat)
}

func (g *generator) cache(parent string, lat time.Time) traceCreating {
	key := g.fill(pick(g, cacheKeys))
	tp := pick(g, cacheTypes)
	data := obj{{"type", tp}, {"key", key}}

	if tp == "set" {
		data = append(data, kv{"cache", obj{{key, obj{{"value", fmt.Sprintf("{\"id\":%d,\"cached_at\":\"%s\"}", g.between(1, 99999), lat.UTC().Format(time.DateTime))}, {"expiration", 3600}}}}})
	}

	return g.child(parent, "cache", "success", []string{tp, key}, g.complement(data), nil, lat)
}

func (g *generator) event(parent string, lat time.Time) traceCreating {
	name := pick(g, eventNames)
	eventListeners := make([]any, 0, 2)

	for range g.between(1, 2) {
		eventListeners = append(eventListeners, obj{{"name", pick(g, listeners)}, {"queued", g.chance(0.3)}})
	}

	data := g.complement(obj{{"name", name}, {"listeners", eventListeners}, {"broadcast", g.chance(0.1)}})

	return g.child(parent, "event", "success", []string{name}, data, nil, lat)
}

func (g *generator) model(parent string, lat time.Time) traceCreating {
	class := pick(g, modelClasses)
	action := pick(g, modelActions)
	changes := any(nil)

	if action == "updated" || action == "created" {
		changes = obj{{"status", pick(g, []string{"new", "paid", "shipped"})}, {"updated_at", lat.UTC().Format(time.DateTime)}}
	}

	data := g.complement(obj{{"action", action}, {"model", class}, {"key", g.between(1, 500000)}, {"changes", changes}})

	return g.child(parent, "model", "success", []string{action, class}, data, nil, lat)
}

func (g *generator) gate(parent string, lat time.Time) traceCreating {
	result := "allowed"
	st := "success"

	if g.chance(0.05) {
		result = "denied"
		st = "failed"
	}

	data := g.complement(obj{
		{"ability", pick(g, abilities)},
		{"result", result},
		{"user_id", g.between(1, 80000)},
		{"arguments", []string{pick(g, modelClasses) + ":" + fmt.Sprint(g.between(1, 90000))}},
	})

	return g.child(parent, "gate", st, []string{result}, data, nil, lat)
}

func (g *generator) log(parent string, lat time.Time, failed bool) traceCreating {
	entry := pick(g, logMessages)

	if failed {
		entry = logMessages[7+g.rnd.IntN(4)]
	}

	context := obj{{"user_id", g.between(1, 80000)}}

	if entry.level == "error" || entry.level == "critical" {
		context = append(context, kv{"exception", obj{
			{"message", entry.message},
			{"exception", "RuntimeException"},
			{"file", "/var/www/app/Services/PaymentService.php"},
			{"line", g.between(20, 300)},
		}})
	}

	data := g.complement(obj{{"level", entry.level}, {"message", entry.message}, {"context", context}})

	return g.child(parent, "log", "success", []string{entry.level}, data, nil, lat)
}

func (g *generator) http(parent string, lat time.Time) traceCreating {
	host := pick(g, httpHosts)
	uri := g.fill(host.uri)
	statusCode := 200
	st := "success"

	if g.chance(0.04) {
		statusCode = pick(g, []int{400, 429, 500, 503})
		st = "failed"
	}

	data := g.complement(obj{
		{"request", obj{
			{"uri", uri},
			{"method", host.method},
			{"headers", obj{{"content-type", "application/json"}, {"authorization", "********"}}},
			{"payload", obj{{"amount", g.between(100, 90000)}, {"currency", "chf"}}},
		}},
		{"response", obj{
			{"status_code", statusCode},
			{"headers", obj{{"content-type", "application/json"}}},
			{"body", obj{{"id", fmt.Sprintf("pi_%x", g.rnd.Uint64())}, {"status", "ok"}}},
		}},
	})

	return g.child(parent, "http-client", st, []string{uri}, data, g.seconds(host.medianMs), lat)
}

func (g *generator) address() obj {
	return obj{{"email", fmt.Sprintf("user%d@example.test", g.between(1, 80000))}, {"full_name", "Customer"}}
}

func (g *generator) mail(parent string, lat time.Time) traceCreating {
	mailable := pick(g, mailables)
	data := g.complement(obj{
		{"mailable", mailable},
		{"queued", g.chance(0.5)},
		{"message", obj{
			{"from", []any{obj{{"email", "noreply@shop.example.test"}, {"full_name", "Shop"}}}},
			{"reply_to", []any{}},
			{"to", []any{g.address()}},
			{"cc", []any{}},
			{"bcc", []any{}},
			{"subject", "Your order"},
		}},
	})

	return g.child(parent, "mail", "success", []string{mailable}, data, nil, lat)
}

func (g *generator) notification(parent string, lat time.Time) traceCreating {
	notification := pick(g, notifications)
	data := g.complement(obj{
		{"notification", notification},
		{"queued", g.chance(0.6)},
		{"notifiable", "App\\Models\\User:" + fmt.Sprint(g.between(1, 80000))},
		{"channel", pick(g, notificationChannels)},
		{"recipients", []any{g.address()}},
		{"response", nil},
	})

	return g.child(parent, "notification", "success", []string{notification}, data, nil, lat)
}

func (g *generator) dump(parent string, lat time.Time) traceCreating {
	data := g.complement(obj{{"dump", fmt.Sprintf("array:3 [\n  \"id\" => %d\n  \"status\" => \"new\"\n  \"total\" => %d\n]", g.between(1, 9999), g.between(1, 9999))}})

	return g.child(parent, "dump", "success", nil, data, nil, lat)
}

func (g *generator) job(lat time.Time) tree {
	j := pick(g, jobClasses)
	tid := g.tid()
	failed := g.chance(0.03)
	duration := g.seconds(j.medianMs)

	root := traceCreating{
		TraceId:  tid,
		Type:     "job",
		Status:   "started",
		Tags:     []string{j.class},
		Data:     g.complement(obj{}),
		LoggedAt: formatLoggedAt(lat),
	}

	status := "processed"
	st := "success"

	if failed {
		status = "failed"
		st = "failed"
	}

	data := obj{
		{"connection_name", "redis"},
		{"job", obj{
			{"name", j.class},
			{"job", "Illuminate\\Queue\\CallQueuedHandler@call"},
			{"max_tries", 3},
			{"fail_on_timeout", false},
			{"data", obj{{"commandName", j.class}, {"batchId", nil}}},
		}},
		{"status", status},
	}

	if failed {
		data = append(data, kv{"exception", obj{{"message", "cURL error 28: Operation timed out"}, {"exception", "GuzzleHttp\\Exception\\ConnectException"}}})
	}

	children := make([]traceCreating, 0, 16)

	at := func() time.Time {
		return lat.Add(time.Duration(g.rnd.Float64() * *duration * float64(time.Second)))
	}

	for range g.between(2, 8) {
		children = append(children, g.database(tid, at()))
	}

	for range g.between(0, 3) {
		children = append(children, g.cache(tid, at()))
	}

	for range g.between(0, 2) {
		children = append(children, g.event(tid, at()))
	}

	for range g.between(0, 3) {
		children = append(children, g.model(tid, at()))
	}

	if failed || g.chance(0.3) {
		children = append(children, g.log(tid, at(), failed))
	}

	if g.chance(0.3) {
		children = append(children, g.http(tid, at()))
	}

	if g.chance(0.1) {
		children = append(children, g.mail(tid, at()))
	}

	if g.chance(0.1) {
		children = append(children, g.notification(tid, at()))
	}

	return tree{
		creating: append([]traceCreating{root}, children...),
		updating: []traceUpdating{{
			TraceId:        tid,
			Status:         st,
			Data:           g.complement(data),
			Duration:       duration,
			Memory:         g.memory(),
			Cpu:            g.cpu(),
			ParentLoggedAt: formatLoggedAt(lat),
		}},
	}
}

func (g *generator) command(lat time.Time) tree {
	c := pick(g, commands)
	tid := g.tid()
	duration := g.seconds(c.medianMs)
	exitCode := 0
	st := "success"

	if g.chance(0.02) {
		exitCode = 1
		st = "failed"
	}

	data := obj{
		{"command", c.name},
		{"exit_code", exitCode},
		{"arguments", obj{{"command", c.name}}},
		{"options", obj{{"help", false}, {"silent", false}, {"quiet", false}, {"verbose", false}, {"version", false}, {"ansi", nil}, {"no-interaction", false}, {"env", nil}}},
	}

	root := traceCreating{
		TraceId:  tid,
		Type:     "command",
		Status:   "started",
		Tags:     []string{c.name},
		Data:     g.complement(obj{{"command", c.name}}, "Illuminate\\Foundation\\Console\\Kernel"),
		LoggedAt: formatLoggedAt(lat),
	}

	children := make([]traceCreating, 0, 8)

	at := func() time.Time {
		return lat.Add(time.Duration(g.rnd.Float64() * *duration * float64(time.Second)))
	}

	if c.name == "schedule:run" {
		for range g.between(1, 5) {
			s := pick(g, scheduledCommands)
			children = append(children, g.child(tid, "schedule", "success", []string{pick(g, []string{"starting", "finished"})}, g.complement(obj{
				{"command", s.command},
				{"description", s.description},
				{"expression", s.expression},
				{"timezone", "UTC"},
				{"user", nil},
				{"output", ""},
			}), nil, at()))
		}
	}

	for range g.between(0, 6) {
		children = append(children, g.database(tid, at()))
	}

	if g.chance(0.3) {
		children = append(children, g.log(tid, at(), st == "failed"))
	}

	return tree{
		creating: append([]traceCreating{root}, children...),
		updating: []traceUpdating{{
			TraceId:        tid,
			Status:         st,
			Data:           g.complement(data, "Illuminate\\Foundation\\Console\\Kernel"),
			Duration:       duration,
			Memory:         g.memory(),
			Cpu:            g.cpu(),
			ParentLoggedAt: formatLoggedAt(lat),
		}},
	}
}

// task is a single trace with nothing under it most of the time, as the runtime's
// periodic tasks send.
func (g *generator) task(lat time.Time) tree {
	name := pick(g, taskNames)
	tid := g.tid()
	result := pick(g, []string{"idle", "idle", "idle", "handled"})

	root := traceCreating{
		TraceId:  tid,
		Type:     "task",
		Status:   "success",
		Tags:     []string{name},
		Data:     g.complement(obj{{"task", name}, {"result", result}}, "SLoggerLaravel\\Processor"),
		Duration: g.seconds(2.5),
		Memory:   g.memory(),
		Cpu:      g.cpu(),
		LoggedAt: formatLoggedAt(lat),
	}

	result2 := tree{creating: []traceCreating{root}}

	if result == "handled" {
		for range g.between(0, 2) {
			result2.creating = append(result2.creating, g.database(tid, lat.Add(time.Millisecond)))
		}

		result2.creating = append(result2.creating, g.event(tid, lat.Add(2*time.Millisecond)))
	}

	return result2
}

// huge is one request with count database children: the tree the UI has to page through.
func (g *generator) huge(lat time.Time, count int) tree {
	result := g.request(lat)
	parent := result.creating[0].TraceId

	for i := range count {
		result.creating = append(result.creating, g.database(parent, lat.Add(time.Duration(i)*time.Millisecond)))
	}

	return result
}
