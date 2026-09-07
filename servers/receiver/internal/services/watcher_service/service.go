package watcher_service

import (
	"context"
	"log/slog"
	"slices"
	"slogger_receiver/internal/repositories/watcher_repository"
	"slogger_receiver/internal/repositories/watcher_timeline_repository"
	"slogger_receiver/pkg/foundation/errs"
	"strconv"
	"strings"
	"sync"
	"sync/atomic"
	"time"

	"go.mongodb.org/mongo-driver/bson/primitive"
)

const (
	// BucketSize is the resolution of a watcher's line. Everything the panel asks of it —
	// windows, baselines — is a sum of whole buckets, so this is the finest question it
	// can ever ask.
	BucketSize = 15 * time.Second

	// maxGroups caps the rollup of one bucket. The bucket's own counters stay exact when
	// it is reached; only the breakdown stops growing.
	maxGroups = 20

	// lateGrace is how long a closed bucket is held before it is written, so that a trace
	// arriving out of order joins it in memory instead of appending a second element with
	// the same timestamp.
	lateGrace = 15 * time.Second

	reloadInterval = 30 * time.Second
	flushInterval  = 15 * time.Second

	// The only match format this binary understands. A watcher carrying anything else is
	// skipped rather than read as if it were this one.
	supportedMatchVersion = 1
)

// matcher is one compiled filter and the watchers that share it.
//
// Watchers with identical filters collapse into one of these: the filter is evaluated per
// trace, and evaluating the same three set lookups twice for two watchers that ask the
// same question is work nobody asked for.
type matcher struct {
	watcherIds []int
	serviceIds map[int]struct{}
	types      map[string]struct{}
	tags       map[string]struct{}
}

// matches answers the part of the filter that the service index has not already answered.
func (m *matcher) matches(traceType string, tags []string) bool {
	if len(m.types) > 0 {
		if _, ok := m.types[traceType]; !ok {
			return false
		}
	}

	if len(m.tags) > 0 {
		found := false

		for _, tag := range tags {
			if _, ok := m.tags[tag]; ok {
				found = true

				break
			}
		}

		if !found {
			return false
		}
	}

	return true
}

type bucketKey struct {
	watcherId int
	at        int64
}

type groupState struct {
	serviceId int
	traceType string
	tags      []string
	count     int
	durCount  int
	durSum    float64
	durMax    float64
	traceId   string
}

type bucketState struct {
	count    int
	durCount int
	durSum   float64
	durMax   float64
	groups   map[string]*groupState
}

var instance *Service
var once sync.Once

func Get() *Service {
	once.Do(func() {
		instance = &Service{
			watchers:  watcher_repository.Get(),
			timelines: watcher_timeline_repository.Get(),
			buckets:   make(map[bucketKey]*bucketState),
			skipped:   make(map[int]int),
		}
	})

	return instance
}

// Service counts traces into the lines of the watchers they belong to.
//
// It knows nothing about what a watcher is for: not its type, not its thresholds, not its
// windows. It reads one column, filters on three dimensions, and adds numbers. Everything
// that turns those numbers into "something is wrong" lives in the panel.
type Service struct {
	watchers  *watcher_repository.Repository
	timelines *watcher_timeline_repository.Repository

	// Read on the hot path before anything else, so an installation with no watchers pays
	// one atomic load per trace and nothing more.
	active atomic.Bool

	matchersMu sync.RWMutex
	byService  map[int][]*matcher
	global     []*matcher
	known      map[int]struct{}

	// Which watchers were skipped and at what version, so an unreadable filter is
	// reported once rather than on every reload.
	skipped map[int]int

	bucketsMu sync.Mutex
	buckets   map[bucketKey]*bucketState
}

// Run keeps the compiled filters current and writes closed buckets out.
//
// It does not flush on the way out: that is the transporter's job, after its loop has
// stopped feeding this. See Transporter.Run.
func (s *Service) Run(ctx context.Context) {
	if err := s.Reload(ctx); err != nil {
		slog.Error(errs.Err(err).Error())
	}

	reload := time.NewTicker(reloadInterval)
	defer reload.Stop()

	flush := time.NewTicker(flushInterval)
	defer flush.Stop()

	for {
		select {
		case <-ctx.Done():
			return
		case <-reload.C:
			if err := s.Reload(ctx); err != nil {
				slog.Error(errs.Err(err).Error())
			}
		case <-flush.C:
			if err := s.Flush(ctx, false); err != nil {
				slog.Error(errs.Err(err).Error())
			}
		}
	}
}

// Reload rebuilds the compiled filters from the watchers table.
//
// A failure leaves the previous set in place: collecting against a filter that is thirty
// seconds stale is better than collecting against none.
func (s *Service) Reload(ctx context.Context) error {
	watchers, err := s.watchers.FindEnabled(ctx)

	if err != nil {
		return errs.Err(err)
	}

	s.compile(watchers)

	return nil
}

// compile turns the stored filters into the form the hot path uses, and publishes it.
//
// Split from Reload so that what it does can be exercised without a database: everything
// this service gets wrong is wrong here.
func (s *Service) compile(watchers []watcher_repository.Watcher) {
	bySignature := make(map[string]*matcher, len(watchers))
	order := make([]string, 0, len(watchers))
	known := make(map[int]struct{}, len(watchers))

	for _, watcher := range watchers {
		if watcher.Match.Version != supportedMatchVersion {
			s.reportSkipped(watcher)

			continue
		}

		known[watcher.Id] = struct{}{}

		signature := matchSignature(watcher.Match)

		if existing, ok := bySignature[signature]; ok {
			existing.watcherIds = append(existing.watcherIds, watcher.Id)

			continue
		}

		bySignature[signature] = &matcher{
			watcherIds: []int{watcher.Id},
			serviceIds: intSet(watcher.Match.ServiceIds),
			types:      stringSet(watcher.Match.Types),
			tags:       stringSet(watcher.Match.Tags),
		}

		order = append(order, signature)
	}

	byService := make(map[int][]*matcher)
	global := make([]*matcher, 0, len(order))

	for _, signature := range order {
		item := bySignature[signature]

		// A filter naming no service is checked against every trace; the rest are reached
		// only through the service they name.
		if len(item.serviceIds) == 0 {
			global = append(global, item)

			continue
		}

		for serviceId := range item.serviceIds {
			byService[serviceId] = append(byService[serviceId], item)
		}
	}

	s.matchersMu.Lock()
	s.byService = byService
	s.global = global
	s.known = known
	s.matchersMu.Unlock()

	s.active.Store(len(order) > 0)

	s.dropUnknownBuckets(known)
}

// Flush writes out the buckets that are done, and everything when told to.
//
// Buckets leave memory whether or not the write succeeds. Retrying them would mean
// holding a growing map against a Mongo that is not answering, and losing fifteen seconds
// of a counter is a smaller failure than a receiver that runs out of memory over it.
func (s *Service) Flush(ctx context.Context, all bool) error {
	buckets := s.takeBuckets(all)

	if len(buckets) == 0 {
		return nil
	}

	if err := s.timelines.Push(ctx, buckets); err != nil {
		return errs.Err(err)
	}

	return nil
}

// AddTrace files one saved trace into the lines of every watcher it belongs to.
//
// isNew says whether the trace was written to its shard for the first time. It is what
// keeps the count honest: a trace reaches the transporter twice, once as it starts and
// once as it finishes, and only the first of those is a new trace. The second is where
// the duration comes from, which is why it is not simply ignored.
func (s *Service) AddTrace(
	serviceId int,
	traceId string,
	traceType string,
	tags []string,
	duration *float64,
	loggedAt time.Time,
	isNew bool,
) {
	if !s.active.Load() {
		return
	}

	// Neither a new trace nor a duration: there is nothing in this write to record.
	if !isNew && duration == nil {
		return
	}

	watcherIds := s.watcherIdsFor(serviceId, traceType, tags)

	if len(watcherIds) == 0 {
		return
	}

	at := loggedAt.UTC().Truncate(BucketSize).UnixMilli()

	signature, sortedTags := groupSignature(serviceId, traceType, tags)

	s.bucketsMu.Lock()
	defer s.bucketsMu.Unlock()

	for _, watcherId := range watcherIds {
		key := bucketKey{watcherId: watcherId, at: at}

		bucket := s.buckets[key]

		if bucket == nil {
			bucket = &bucketState{groups: make(map[string]*groupState)}

			s.buckets[key] = bucket
		}

		group := bucket.groups[signature]

		if group == nil && len(bucket.groups) < maxGroups {
			group = &groupState{
				serviceId: serviceId,
				traceType: traceType,
				tags:      sortedTags,
			}

			bucket.groups[signature] = group
		}

		if isNew {
			bucket.count++

			if group != nil {
				group.count++
			}
		}

		if duration != nil {
			bucket.durCount++
			bucket.durSum += *duration

			if *duration > bucket.durMax {
				bucket.durMax = *duration
			}

			if group != nil {
				group.durCount++
				group.durSum += *duration

				// The slowest trace of the group keeps its name, so the panel can point
				// at something rather than only report a number.
				if *duration > group.durMax || group.traceId == "" {
					group.durMax = *duration
					group.traceId = traceId
				}
			}
		}
	}
}

func (s *Service) watcherIdsFor(serviceId int, traceType string, tags []string) []int {
	s.matchersMu.RLock()
	scoped := s.byService[serviceId]
	global := s.global
	s.matchersMu.RUnlock()

	var watcherIds []int

	for _, item := range scoped {
		if item.matches(traceType, tags) {
			watcherIds = append(watcherIds, item.watcherIds...)
		}
	}

	for _, item := range global {
		if item.matches(traceType, tags) {
			watcherIds = append(watcherIds, item.watcherIds...)
		}
	}

	return watcherIds
}

// takeBuckets removes the buckets that are ready and turns them into what the repository
// writes.
func (s *Service) takeBuckets(all bool) map[int][]watcher_timeline_repository.Bucket {
	closedBefore := time.Now().UTC().Add(-lateGrace)

	s.bucketsMu.Lock()
	defer s.bucketsMu.Unlock()

	result := make(map[int][]watcher_timeline_repository.Bucket)

	for key, bucket := range s.buckets {
		at := time.UnixMilli(key.at).UTC()

		if !all && at.Add(BucketSize).After(closedBefore) {
			continue
		}

		delete(s.buckets, key)

		result[key.watcherId] = append(result[key.watcherId], bucket.toDocument(at))
	}

	return result
}

// dropUnknownBuckets forgets what was collected for watchers that are no longer active.
//
// Otherwise a watcher deleted in the panel would have its document written again by the
// next flush, and nothing would ever remove it: the collection has no TTL, because a live
// watcher's line must not expire.
func (s *Service) dropUnknownBuckets(known map[int]struct{}) {
	s.bucketsMu.Lock()
	defer s.bucketsMu.Unlock()

	for key := range s.buckets {
		if _, ok := known[key.watcherId]; !ok {
			delete(s.buckets, key)
		}
	}
}

func (s *Service) reportSkipped(watcher watcher_repository.Watcher) {
	s.matchersMu.Lock()
	defer s.matchersMu.Unlock()

	if version, ok := s.skipped[watcher.Id]; ok && version == watcher.Match.Version {
		return
	}

	s.skipped[watcher.Id] = watcher.Match.Version

	slog.Warn(
		"watcher skipped: its filter is of a version this receiver does not understand",
		"watcher", watcher.Id,
		"version", watcher.Match.Version,
	)
}

func (b *bucketState) toDocument(at time.Time) watcher_timeline_repository.Bucket {
	groups := make([]watcher_timeline_repository.Group, 0, len(b.groups))

	for _, group := range b.groups {
		groups = append(groups, watcher_timeline_repository.Group{
			ServiceId: group.serviceId,
			Type:      group.traceType,
			Tags:      group.tags,
			Count:     group.count,
			DurCount:  group.durCount,
			DurSum:    group.durSum,
			DurMax:    group.durMax,
			TraceId:   group.traceId,
		})
	}

	return watcher_timeline_repository.Bucket{
		At:       primitive.NewDateTimeFromTime(at),
		Count:    b.count,
		DurCount: b.durCount,
		DurSum:   b.durSum,
		DurMax:   b.durMax,
		Groups:   groups,
	}
}

// groupSignature identifies the shape of a trace inside a bucket, and hands back the tags
// in the order the signature used them so the stored group reads the same way.
func groupSignature(serviceId int, traceType string, tags []string) (string, []string) {
	// Built empty rather than nil-copied: a nil slice is written as null, and a group with
	// no tags should read as a group with no tags, not as one whose tags are unknown.
	sorted := make([]string, 0, len(tags))
	sorted = append(sorted, tags...)

	slices.Sort(sorted)
	sorted = slices.Compact(sorted)

	var builder strings.Builder

	builder.WriteString(strconv.Itoa(serviceId))
	builder.WriteByte(0)
	builder.WriteString(traceType)
	builder.WriteByte(0)
	builder.WriteString(strings.Join(sorted, "\x00"))

	return builder.String(), sorted
}

// matchSignature is what makes two watchers asking the same question share one matcher.
func matchSignature(match watcher_repository.Match) string {
	serviceIds := append([]int(nil), match.ServiceIds...)
	slices.Sort(serviceIds)
	serviceIds = slices.Compact(serviceIds)

	parts := make([]string, 0, len(serviceIds))

	for _, serviceId := range serviceIds {
		parts = append(parts, strconv.Itoa(serviceId))
	}

	return strings.Join(parts, ",") +
		"|" + strings.Join(sortedUnique(match.Types), ",") +
		"|" + strings.Join(sortedUnique(match.Tags), ",")
}

func sortedUnique(values []string) []string {
	sorted := append([]string(nil), values...)

	slices.Sort(sorted)

	return slices.Compact(sorted)
}

func intSet(values []int) map[int]struct{} {
	set := make(map[int]struct{}, len(values))

	for _, value := range values {
		set[value] = struct{}{}
	}

	return set
}

func stringSet(values []string) map[string]struct{} {
	set := make(map[string]struct{}, len(values))

	for _, value := range values {
		set[value] = struct{}{}
	}

	return set
}
