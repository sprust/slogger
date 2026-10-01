// loadgen pushes generated traces through the receiver's socket, the way the Laravel
// client does, to load the whole ingestion path: socket → buffer → transporter →
// ClickHouse.
//
//	go run ./cmd/loadgen -tokens <token1>,<token2> -hours 24 -per-hour 500000 -workers 8
//
// Traces are spread over the last -hours hours, -per-hour of them in each. Every tree is
// a root (request, job, command or task) with the children an application of that kind
// sends. A small share of them arrives the awkward way: the update before the create,
// the create twice, the create without an update.
package main

import (
	"context"
	"encoding/json"
	"flag"
	"fmt"
	"log"
	"os"
	"os/signal"
	"slices"
	"strings"
	"sync"
	"sync/atomic"
	"syscall"
	"time"

	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
)

type config struct {
	address       string
	tokens        []string
	hours         int
	perHour       int
	workers       int
	batch         int
	chunk         int
	end           time.Time
	seed          uint64
	tidPrefix     string
	anomalyRate   float64
	bigDtRate     float64
	hugeChildren  int
	mongoUri      string
	mongoDatabase string
	mongoBuffer   string
	bufferMax     int64
	progressFile  string
	timeout       time.Duration
}

type chunk struct {
	from  time.Time
	count int
}

// progress is written to -progress-file every few seconds and once more at the end.
type progress struct {
	StartedAt      time.Time        `json:"started_at"`
	UpdatedAt      time.Time        `json:"updated_at"`
	Finished       bool             `json:"finished"`
	Target         int64            `json:"target"`
	Created        int64            `json:"created"`
	CreatedByToken map[string]int64 `json:"created_by_token"`
	Updated        int64            `json:"updated"`
	Messages       int64            `json:"messages"`
	Errors         int64            `json:"errors"`
	Dropped        int64            `json:"dropped"`
	LastError      string           `json:"last_error,omitempty"`
	RatePerSecond  float64          `json:"rate_per_second"`
	AckAvgMs       float64          `json:"ack_avg_ms"`
	AckP99Ms       float64          `json:"ack_p99_ms"`
	BufferDocs     int64            `json:"buffer_docs"`
	ThrottledMs    int64            `json:"throttled_ms"`
}

type counters struct {
	created   atomic.Int64
	updated   atomic.Int64
	messages  atomic.Int64
	errors    atomic.Int64
	throttled atomic.Int64
	dropped   atomic.Int64
	buffer    atomic.Int64
	byToken   []atomic.Int64

	mutex     sync.Mutex
	lastError string
	acks      []float64
}

func (c *counters) ack(ms float64) {
	c.mutex.Lock()
	c.acks = append(c.acks, ms)
	c.mutex.Unlock()
}

func (c *counters) fail(err error) {
	c.errors.Add(1)
	c.mutex.Lock()
	c.lastError = err.Error()
	c.mutex.Unlock()
}

func main() {
	cfg := parseFlags()

	ctx, cancel := signal.NotifyContext(context.Background(), os.Interrupt, syscall.SIGTERM)
	defer cancel()

	stats := &counters{byToken: make([]atomic.Int64, len(cfg.tokens))}

	if cfg.bufferMax > 0 {
		go watchBuffer(ctx, cfg, stats)
	}

	startedAt := time.Now()
	target := int64(cfg.hours * cfg.perHour)

	reporterDone := make(chan struct{})

	go func() {
		defer close(reporterDone)

		ticker := time.NewTicker(5 * time.Second)
		defer ticker.Stop()

		previous, previousAt := int64(0), time.Now()

		for {
			select {
			case <-ctx.Done():
				return
			case <-ticker.C:
				created := stats.created.Load()
				rate := float64(created-previous) / time.Since(previousAt).Seconds()
				previous, previousAt = created, time.Now()

				writeProgress(cfg, stats, startedAt, target, rate, false)
				log.Printf("created %d/%d (%.0f/s), buffer %d, errors %d", created, target, rate, stats.buffer.Load(), stats.errors.Load())
			}
		}
	}()

	chunks := make(chan chunk)

	go func() {
		defer close(chunks)

		// hours oldest first, as a live application would have sent them
		for h := cfg.hours; h >= 1; h-- {
			from := cfg.end.Add(-time.Duration(h) * time.Hour)

			for left := cfg.perHour; left > 0; left -= cfg.chunk {
				select {
				case <-ctx.Done():
					return
				case chunks <- chunk{from: from, count: min(left, cfg.chunk)}:
				}
			}
		}
	}()

	var wg sync.WaitGroup

	for i := range cfg.workers {
		wg.Add(1)

		go func() {
			defer wg.Done()

			runWorker(ctx, cfg, stats, i, chunks)
		}()
	}

	if cfg.hugeChildren > 0 {
		wg.Add(1)

		go func() {
			defer wg.Done()

			sendHuge(ctx, cfg, stats)
		}()
	}

	wg.Wait()
	cancel()
	<-reporterDone

	writeProgress(cfg, stats, startedAt, target, 0, true)

	log.Printf("done: created %d, updated %d, messages %d, errors %d, dropped %d in %s",
		stats.created.Load(), stats.updated.Load(), stats.messages.Load(), stats.errors.Load(), stats.dropped.Load(), time.Since(startedAt).Round(time.Second))
}

func parseFlags() config {
	var cfg config
	var tokens, end string

	flag.StringVar(&cfg.address, "addr", "localhost:53407", "receiver socket address")
	flag.StringVar(&tokens, "tokens", "", "comma-separated service api tokens, one per worker in turn")
	flag.IntVar(&cfg.hours, "hours", 24, "hours back from -end to fill")
	flag.IntVar(&cfg.perHour, "per-hour", 500000, "traces per hour")
	flag.IntVar(&cfg.workers, "workers", 8, "parallel connections")
	flag.IntVar(&cfg.batch, "batch", 200, "creating traces per message")
	flag.IntVar(&cfg.chunk, "chunk", 5000, "traces a worker takes at once")
	flag.StringVar(&end, "end", "", "end of the period, RFC3339 (default now)")
	flag.Uint64Var(&cfg.seed, "seed", uint64(time.Now().UnixNano()), "random seed")
	flag.StringVar(&cfg.tidPrefix, "tid-prefix", "stress", "prefix of generated trace ids")
	flag.Float64Var(&cfg.anomalyRate, "anomaly-rate", 0.02, "share of trees sent the awkward way")
	flag.Float64Var(&cfg.bigDtRate, "big-dt-rate", 0.0002, "share of requests with a 100KB response body")
	flag.IntVar(&cfg.hugeChildren, "huge-children", 0, "also send one request with this many children")
	flag.StringVar(&cfg.mongoUri, "mongo-uri", "mongodb://sl_admin:_sl_password_3124@localhost:53405", "MongoDB of the receiver, to watch the buffer")
	flag.StringVar(&cfg.mongoDatabase, "mongo-db", "traces", "database of the buffer")
	flag.StringVar(&cfg.mongoBuffer, "mongo-buffer", "buffer", "buffer collection")
	flag.Int64Var(&cfg.bufferMax, "buffer-max", 1000000, "pause while the buffer holds more documents than this, 0 to never pause")
	flag.StringVar(&cfg.progressFile, "progress-file", "", "where to write progress JSON")
	flag.DurationVar(&cfg.timeout, "timeout", 30*time.Second, "socket timeout")
	flag.Parse()

	for _, token := range strings.Split(tokens, ",") {
		if token = strings.TrimSpace(token); token != "" {
			cfg.tokens = append(cfg.tokens, token)
		}
	}

	if len(cfg.tokens) == 0 {
		log.Fatal("-tokens is required")
	}

	cfg.end = time.Now()

	if end != "" {
		parsed, err := time.Parse(time.RFC3339, end)

		if err != nil {
			log.Fatalf("-end: %v", err)
		}

		cfg.end = parsed
	}

	return cfg
}

// rootTypes weighs the roots: requests and the tasks of the runtime most of all.
var rootTypes = []struct {
	name   string
	weight float64
}{
	{"request", 0.40},
	{"job", 0.22},
	{"command", 0.04},
	{"task", 0.34},
}

func pickRootType(g *generator) string {
	r := g.rnd.Float64()

	for _, root := range rootTypes {
		if r < root.weight {
			return root.name
		}

		r -= root.weight
	}

	return "task"
}

func runWorker(ctx context.Context, cfg config, stats *counters, index int, chunks <-chan chunk) {
	tokenIndex := index % len(cfg.tokens)
	s := newSender(cfg.address, cfg.tokens[tokenIndex], cfg.timeout)
	defer s.close()

	g := newGenerator(cfg.seed+uint64(index)*7919, cfg.tidPrefix, cfg.bigDtRate)

	var creating, deferredCreating []traceCreating
	var updating []traceUpdating
	var batchCreated int64

	flush := func() bool {
		if len(creating) == 0 && len(updating) == 0 {
			return true
		}

		delivered, ok := send(ctx, cfg, stats, s, creating, updating)

		if !ok {
			return false
		}

		if delivered {
			stats.created.Add(batchCreated)
			stats.byToken[tokenIndex].Add(batchCreated)
			stats.updated.Add(int64(len(updating)))
		}

		// what was held back goes in the next message: an update already sent without its
		// create, or a create sent a second time
		creating, updating, deferredCreating, batchCreated = deferredCreating, nil, nil, 0

		return true
	}

	for c := range chunks {
		created := 0

		for created < c.count {
			lat := c.from.Add(time.Duration(g.rnd.Int64N(int64(time.Hour))))

			if lat.After(cfg.end) {
				lat = cfg.end.Add(-time.Duration(g.rnd.Int64N(int64(time.Minute))))
			}

			t := g.generate(pickRootType(g), lat)

			created += len(t.creating)
			batchCreated += int64(len(t.creating))

			switch r := g.rnd.Float64(); {
			case len(t.updating) > 0 && r < cfg.anomalyRate*0.5:
				// the update arrives first, its create one message later
				deferredCreating = append(deferredCreating, t.creating[0])
				creating = append(creating, t.creating[1:]...)
				updating = append(updating, t.updating...)
			case r < cfg.anomalyRate*0.75:
				// the create arrives twice
				creating = append(creating, t.creating...)
				updating = append(updating, t.updating...)
				deferredCreating = append(deferredCreating, t.creating[0])
			case len(t.updating) > 0 && r < cfg.anomalyRate:
				// the update never arrives
				creating = append(creating, t.creating...)
			default:
				creating = append(creating, t.creating...)
				updating = append(updating, t.updating...)
			}

			if len(creating) >= cfg.batch {
				if !flush() {
					return
				}
			}
		}
	}

	flush()

	// a create held back by the last message still has to go
	flush()
}

// send says whether the message was delivered, and whether to go on: a message given up after
// its attempts is not delivered, but the run goes on.
func send(ctx context.Context, cfg config, stats *counters, s *sender, creating []traceCreating, updating []traceUpdating) (bool, bool) {
	for cfg.bufferMax > 0 && stats.buffer.Load() > cfg.bufferMax {
		select {
		case <-ctx.Done():
			return false, false
		case <-time.After(500 * time.Millisecond):
			stats.throttled.Add(500)
		}
	}

	for attempt := 1; ; attempt++ {
		if ctx.Err() != nil {
			return false, false
		}

		startedAt := time.Now()
		err := s.send(creating, updating)

		if err == nil {
			stats.messages.Add(1)
			stats.ack(float64(time.Since(startedAt).Microseconds()) / 1000)

			return true, true
		}

		stats.fail(err)

		if attempt >= 10 {
			log.Printf("giving up a message after %d attempts: %v", attempt, err)

			stats.dropped.Add(1)

			return false, true
		}

		select {
		case <-ctx.Done():
			return false, false
		case <-time.After(time.Duration(attempt) * time.Second):
		}
	}
}

func sendHuge(ctx context.Context, cfg config, stats *counters) {
	s := newSender(cfg.address, cfg.tokens[0], cfg.timeout)
	defer s.close()

	g := newGenerator(cfg.seed^0xabcdef, cfg.tidPrefix+"-huge", 0)
	t := g.huge(cfg.end.Add(-30*time.Minute), cfg.hugeChildren)

	log.Printf("huge tree: root %s with %d children", t.creating[0].TraceId, cfg.hugeChildren)

	for i := 0; i < len(t.creating); i += cfg.batch * 5 {
		part := t.creating[i:min(i+cfg.batch*5, len(t.creating))]

		delivered, ok := send(ctx, cfg, stats, s, part, nil)

		if !ok {
			return
		}

		if delivered {
			stats.created.Add(int64(len(part)))
			stats.byToken[0].Add(int64(len(part)))
		}
	}

	if delivered, _ := send(ctx, cfg, stats, s, nil, t.updating); delivered {
		stats.updated.Add(int64(len(t.updating)))
	}
}

func watchBuffer(ctx context.Context, cfg config, stats *counters) {
	client, err := mongo.Connect(ctx, options.Client().ApplyURI(cfg.mongoUri))

	if err != nil {
		log.Fatalf("mongo: %v", err)
	}

	defer func() {
		_ = client.Disconnect(context.Background())
	}()

	collection := client.Database(cfg.mongoDatabase).Collection(cfg.mongoBuffer)

	for ctx.Err() == nil {
		count, err := collection.EstimatedDocumentCount(ctx)

		if err == nil {
			stats.buffer.Store(count)
		} else if ctx.Err() == nil {
			log.Printf("buffer count: %v", err)
		}

		select {
		case <-ctx.Done():
		case <-time.After(time.Second):
		}
	}
}

func writeProgress(cfg config, stats *counters, startedAt time.Time, target int64, rate float64, finished bool) {
	stats.mutex.Lock()
	acks := stats.acks
	stats.acks = nil
	lastError := stats.lastError
	stats.mutex.Unlock()

	var avg, p99 float64

	if len(acks) > 0 {
		slices.Sort(acks)

		for _, ack := range acks {
			avg += ack
		}

		avg /= float64(len(acks))
		p99 = acks[min(len(acks)-1, len(acks)*99/100)]
	}

	byToken := map[string]int64{}

	for i, token := range cfg.tokens {
		byToken[token[:min(8, len(token))]] = stats.byToken[i].Load()
	}

	p := progress{
		StartedAt:      startedAt,
		UpdatedAt:      time.Now(),
		Finished:       finished,
		Target:         target,
		Created:        stats.created.Load(),
		CreatedByToken: byToken,
		Updated:        stats.updated.Load(),
		Messages:       stats.messages.Load(),
		Errors:         stats.errors.Load(),
		Dropped:        stats.dropped.Load(),
		LastError:      lastError,
		RatePerSecond:  rate,
		AckAvgMs:       avg,
		AckP99Ms:       p99,
		BufferDocs:     stats.buffer.Load(),
		ThrottledMs:    stats.throttled.Load(),
	}

	if cfg.progressFile == "" {
		return
	}

	encoded, err := json.MarshalIndent(p, "", "  ")

	if err != nil {
		log.Printf("progress: %v", err)

		return
	}

	tmp := cfg.progressFile + ".tmp"

	if err := os.WriteFile(tmp, encoded, 0o644); err != nil {
		log.Printf("progress: %v", err)

		return
	}

	if err := os.Rename(tmp, cfg.progressFile); err != nil {
		log.Printf("progress: %v", fmt.Errorf("rename: %w", err))
	}
}
