package watcher_service

import (
	"slogger_receiver/internal/repositories/watcher_repository"
	"slogger_receiver/internal/repositories/watcher_timeline_repository"
	"strconv"
	"testing"
	"time"
)

// The filter is the whole of what this service knows about a watcher, and getting it
// wrong is invisible: a watcher counting the wrong traces still produces a number.

func TestAnEmptyFilterTakesEveryTrace(t *testing.T) {
	item := &matcher{}

	if !item.matches("http", nil) {
		t.Fatal("a filter naming nothing should take every trace")
	}
}

func TestATypeFilterTakesOnlyThatType(t *testing.T) {
	item := &matcher{types: stringSet([]string{"http", "db"})}

	if !item.matches("db", nil) {
		t.Fatal("a listed type should match")
	}

	if item.matches("queue", nil) {
		t.Fatal("a type that is not listed should not match")
	}
}

// Tags are "any of", not "all of": a watcher listing two tags is watching either.
func TestATagFilterTakesATraceCarryingAnyOfThem(t *testing.T) {
	item := &matcher{tags: stringSet([]string{"billing", "auth"})}

	if !item.matches("http", []string{"other", "auth"}) {
		t.Fatal("one listed tag out of several should match")
	}

	if item.matches("http", []string{"other"}) {
		t.Fatal("no listed tag should not match")
	}

	if item.matches("http", nil) {
		t.Fatal("a trace with no tags cannot satisfy a tag filter")
	}
}

func TestTypeAndTagsHaveToHoldTogether(t *testing.T) {
	item := &matcher{
		types: stringSet([]string{"http"}),
		tags:  stringSet([]string{"billing"}),
	}

	if item.matches("http", []string{"auth"}) {
		t.Fatal("the right type with the wrong tag should not match")
	}

	if item.matches("db", []string{"billing"}) {
		t.Fatal("the right tag with the wrong type should not match")
	}

	if !item.matches("http", []string{"billing"}) {
		t.Fatal("both satisfied should match")
	}
}

// Two watchers asking the same question share one matcher, whatever order their lists
// were written in.
func TestTheSameFilterWrittenDifferentlyIsTheSameMatcher(t *testing.T) {
	first := matchSignature(watcher_repository.Match{
		ServiceIds: []int{7, 3},
		Types:      []string{"db", "http"},
		Tags:       []string{"b", "a"},
	})

	second := matchSignature(watcher_repository.Match{
		ServiceIds: []int{3, 7},
		Types:      []string{"http", "db"},
		Tags:       []string{"a", "b"},
	})

	if first != second {
		t.Fatalf("the same filter produced two signatures: %q and %q", first, second)
	}
}

// A separator that can appear inside a tag is not a separator. With a comma between the
// items and a pipe between the sections, a watcher on the one tag "a,b" signed exactly
// like a watcher on the two tags "a" and "b" — they were folded into one matcher, and the
// second then collected the first's traffic under a filter nobody gave it.
func TestATagContainingASeparatorDoesNotCollideWithTwoTags(t *testing.T) {
	first := matchSignature(watcher_repository.Match{Tags: []string{"a,b"}})
	second := matchSignature(watcher_repository.Match{Tags: []string{"a", "b"}})

	if first == second {
		t.Fatalf("two different tag filters produced the same signature: %q", first)
	}

	withTypes := matchSignature(watcher_repository.Match{Types: []string{"a|b"}})
	withTags := matchSignature(watcher_repository.Match{Types: []string{"a"}, Tags: []string{"b"}})

	if withTypes == withTags {
		t.Fatalf("a type and a tag ran together into one signature: %q", withTypes)
	}
}

func TestDifferentFiltersAreDifferentMatchers(t *testing.T) {
	first := matchSignature(watcher_repository.Match{Types: []string{"http"}})
	second := matchSignature(watcher_repository.Match{Tags: []string{"http"}})

	if first == second {
		t.Fatal("a type filter and a tag filter of the same word must not collide")
	}
}

// A trace reaches the transporter twice — once as it starts, once as it finishes — and
// only the first of those is a new trace. Counting both would double every number the
// watchers are built on.
func TestATraceIsCountedOnceAndItsDurationArrivesLater(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1})

	at := time.Date(2026, 9, 7, 10, 0, 3, 0, time.UTC)
	duration := 12.5

	service.AddTrace(1, "trace-1", "http", nil, nil, at, true)
	service.AddTrace(1, "trace-1", "http", nil, &duration, at, false)

	byTime := bucketsByTime(t, service)

	started := bucketAt(t, byTime, time.Date(2026, 9, 7, 10, 0, 0, 0, time.UTC))
	finished := bucketAt(t, byTime, time.Date(2026, 9, 7, 10, 0, 15, 0, time.UTC))

	if started.Count != 1 || started.DurCount != 0 {
		t.Fatalf("the start bucket should hold the count and nothing else: %+v", started)
	}

	if finished.Count != 0 || finished.DurCount != 1 || finished.DurMax != 12.5 || finished.DurSum != 12.5 {
		t.Fatalf("the duration belongs to the bucket it became true in: %+v", finished)
	}
}

// The point of filing a duration under its completion: a trace slower than the watcher's
// own window used to land in a bucket the window could never reach, so the slower the
// trace the more certainly nothing was raised.
func TestADurationLandsInTheBucketItBecameTrueIn(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1})

	at := time.Date(2026, 9, 7, 10, 0, 0, 0, time.UTC)
	duration := 600.0

	service.AddTrace(1, "slow", "http", nil, nil, at, true)
	service.AddTrace(1, "slow", "http", nil, &duration, at, false)

	finished := bucketAt(t, bucketsByTime(t, service), time.Date(2026, 9, 7, 10, 10, 0, 0, time.UTC))

	if finished.DurMax != 600 {
		t.Fatalf("a ten-minute trace should be visible ten minutes on: %+v", finished)
	}
}

// A duration from a clock running ahead is clamped, like the start already was: a bucket
// in the future is one no window ever closes over.
func TestADurationRunningPastNowIsClamped(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1})

	at := time.Now().UTC().Add(-time.Second)
	duration := 3600.0

	service.AddTrace(1, "wrong-clock", "http", nil, &duration, at, true)

	buckets := service.takeBuckets(true)

	for _, watcherBuckets := range buckets {
		for _, bucket := range watcherBuckets {
			if bucket.At.Time().After(time.Now().UTC().Add(time.Second)) {
				t.Fatalf("a bucket was made in the future: %v", bucket.At.Time())
			}
		}
	}
}

// An update that brings nothing — no duration, and not a new trace — must not conjure a
// bucket out of nothing: an empty bucket in the line reads as "traces were seen here".
func TestAnUpdateWithoutADurationRecordsNothing(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1})

	service.AddTrace(1, "trace-1", "http", nil, nil, time.Now().UTC(), false)

	if buckets := service.takeBuckets(true); len(buckets) != 0 {
		t.Fatalf("expected nothing collected, got %+v", buckets)
	}
}

// A bucket is chosen by loggedAt, which the client sends. A clock running ahead would
// otherwise make one that is never closed: held in memory for ever, and once the shutdown
// flush writes it, sitting at the tail of the line where the panel's cutoff — which
// deletes by "older than" — can never reach it, holding a slot in the 720-bucket ceiling.
func TestATraceFromTheFutureLandsInTheCurrentBucket(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1})

	service.AddTrace(1, "trace-1", "http", nil, nil, time.Now().UTC().AddDate(1, 0, 0), true)

	bucket := onlyBucket(t, service)

	if bucket.At.Time().UTC().After(time.Now().UTC()) {
		t.Fatalf("the bucket was put in the future: %s", bucket.At.Time())
	}
}

func TestTracesOfDifferentShapesLandInDifferentGroups(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1})

	at := time.Date(2026, 9, 7, 10, 0, 0, 0, time.UTC)

	service.AddTrace(1, "trace-1", "http", []string{"a"}, nil, at, true)
	service.AddTrace(1, "trace-2", "http", []string{"a"}, nil, at, true)
	service.AddTrace(1, "trace-3", "db", []string{"a"}, nil, at, true)

	bucket := onlyBucket(t, service)

	if bucket.Count != 3 {
		t.Fatalf("expected three traces, got %d", bucket.Count)
	}

	if len(bucket.Groups) != 2 {
		t.Fatalf("expected two shapes, got %d", len(bucket.Groups))
	}
}

// The tags of a group are the ones its signature was built from, so two traces carrying
// the same tags in a different order are one shape rather than two.
func TestTagOrderDoesNotSplitAGroup(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1})

	at := time.Date(2026, 9, 7, 10, 0, 0, 0, time.UTC)

	service.AddTrace(1, "trace-1", "http", []string{"b", "a"}, nil, at, true)
	service.AddTrace(1, "trace-2", "http", []string{"a", "b"}, nil, at, true)

	bucket := onlyBucket(t, service)

	if len(bucket.Groups) != 1 {
		t.Fatalf("expected one shape, got %d", len(bucket.Groups))
	}

	if bucket.Groups[0].Count != 2 {
		t.Fatalf("expected two traces in the shape, got %d", bucket.Groups[0].Count)
	}
}

// The group keeps the name of its slowest trace: that is what lets the panel point at
// something instead of only reporting a number.
func TestAGroupKeepsTheSlowestTrace(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1})

	at := time.Date(2026, 9, 7, 10, 0, 0, 0, time.UTC)

	quick := 1.0
	slow := 30.0

	service.AddTrace(1, "quick", "http", nil, &quick, at, true)
	service.AddTrace(1, "slow", "http", nil, &slow, at, true)

	// The slow one finishes half a minute on, so its duration is filed there.
	bucket := bucketAt(t, bucketsByTime(t, service), time.Date(2026, 9, 7, 10, 0, 30, 0, time.UTC))

	if bucket.Groups[0].TraceId != "slow" {
		t.Fatalf("expected the slowest trace to be named, got %q", bucket.Groups[0].TraceId)
	}

	if bucket.Groups[0].DurMax != 30 {
		t.Fatalf("expected the longest duration, got %v", bucket.Groups[0].DurMax)
	}
}

// The slowest trace is filed under the moment it finished, and found by the moment it
// started. Both have to travel together, or the panel looks for it in the wrong hour.
func TestAGroupDatesItsSlowestTraceByItsStart(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1})

	quickStart := time.Date(2026, 9, 7, 10, 0, 0, 0, time.UTC)
	slowStart := time.Date(2026, 9, 7, 9, 0, 30, 0, time.UTC)

	quick := 1.0
	slow := 3600.0

	service.AddTrace(1, "quick", "http", nil, &quick, quickStart, true)
	service.AddTrace(1, "slow", "http", nil, &slow, slowStart, true)

	bucket := bucketAt(t, bucketsByTime(t, service), time.Date(2026, 9, 7, 10, 0, 30, 0, time.UTC))

	if bucket.Groups[0].TraceId != "slow" {
		t.Fatalf("expected the slowest trace to be named, got %q", bucket.Groups[0].TraceId)
	}

	if !bucket.Groups[0].TraceLoggedAt.Time().Equal(slowStart) {
		t.Fatalf("expected the slowest trace to be dated by its start, got %v", bucket.Groups[0].TraceLoggedAt.Time())
	}
}

// A shape whose traces have only started has no slowest trace, and so no date to give.
func TestAGroupWithoutAFinishedTraceHasNoDate(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1})

	service.AddTrace(1, "running", "http", nil, nil, time.Date(2026, 9, 7, 10, 0, 0, 0, time.UTC), true)

	bucket := onlyBucket(t, service)

	if bucket.Groups[0].TraceLoggedAt != 0 {
		t.Fatalf("expected no date for a group without a finished trace, got %v", bucket.Groups[0].TraceLoggedAt.Time())
	}
}

// The rollup is capped, and the cap must not cost the bucket its count: the breakdown is
// a convenience, the number is the measurement.
func TestTheRollupIsCappedButTheCountStaysExact(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1})

	at := time.Date(2026, 9, 7, 10, 0, 0, 0, time.UTC)

	for i := 0; i < maxGroups*2; i++ {
		service.AddTrace(1, "trace", "type-"+string(rune('a'+i)), nil, nil, at, true)
	}

	bucket := onlyBucket(t, service)

	if len(bucket.Groups) != maxGroups {
		t.Fatalf("expected the rollup capped at %d, got %d", maxGroups, len(bucket.Groups))
	}

	if bucket.Count != maxGroups*2 {
		t.Fatalf("expected every trace counted, got %d", bucket.Count)
	}
}

func TestTracesFallIntoTheBucketOfTheirLoggedAt(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1})

	base := time.Date(2026, 9, 7, 10, 0, 0, 0, time.UTC)

	service.AddTrace(1, "trace-1", "http", nil, nil, base.Add(2*time.Second), true)
	service.AddTrace(1, "trace-2", "http", nil, nil, base.Add(14*time.Second), true)
	service.AddTrace(1, "trace-3", "http", nil, nil, base.Add(16*time.Second), true)

	buckets := service.takeBuckets(true)[1]

	if len(buckets) != 2 {
		t.Fatalf("expected two buckets, got %d", len(buckets))
	}

	counts := map[int64]int{}

	for _, bucket := range buckets {
		counts[bucket.At.Time().UTC().Unix()] = bucket.Count
	}

	if counts[base.Unix()] != 2 {
		t.Fatalf("expected two traces in the first bucket, got %d", counts[base.Unix()])
	}

	if counts[base.Add(15*time.Second).Unix()] != 1 {
		t.Fatalf("expected one trace in the second bucket, got %d", counts[base.Add(15*time.Second).Unix()])
	}
}

// A bucket that is still open is not written: a partial one, read as a window, looks like
// a drop in traffic.
func TestAnOpenBucketIsNotWrittenYet(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1})

	service.AddTrace(1, "trace-1", "http", nil, nil, time.Now().UTC(), true)

	if buckets := service.takeBuckets(false); len(buckets) != 0 {
		t.Fatal("the bucket in progress should not have been written")
	}

	if buckets := service.takeBuckets(true); len(buckets) != 1 {
		t.Fatal("a forced flush should take the bucket in progress")
	}
}

// A filter this binary cannot read is refused rather than guessed at.
func TestAWatcherOfAnUnknownFilterVersionIsNotCollectedFor(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: supportedMatchVersion + 1})

	if service.active.Load() {
		t.Fatal("a watcher with an unreadable filter must not make the service active")
	}

	service.AddTrace(1, "trace-1", "http", nil, nil, time.Now().UTC(), true)

	if buckets := service.takeBuckets(true); len(buckets) != 0 {
		t.Fatalf("expected nothing collected, got %+v", buckets)
	}
}

// What was collected for a watcher that is gone is dropped rather than written: the
// collection has no TTL, so a document recreated after the panel deleted it stays for
// ever.
func TestBucketsOfAWatcherThatIsGoneAreDropped(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1})

	service.AddTrace(1, "trace-1", "http", nil, nil, time.Now().UTC(), true)

	service.dropUnknownBuckets(map[int]struct{}{})

	if buckets := service.takeBuckets(true); len(buckets) != 0 {
		t.Fatalf("expected the buckets dropped, got %+v", buckets)
	}
}

// newTestService builds a service with its filters already compiled, so that nothing here
// reaches MySQL.
func newTestService(watcherId int, match watcher_repository.Match) *Service {
	service := &Service{
		buckets: make(map[bucketKey]*bucketState),
		skipped: make(map[int]int),
	}

	service.compile([]watcher_repository.Watcher{{Id: watcherId, Match: match}})

	return service
}

// bucketsByTime takes everything the service holds, once: takeBuckets empties it, so a
// test that looks at two moments has to read them from the same snapshot.
func bucketsByTime(t *testing.T, service *Service) map[int64]watcher_timeline_repository.Bucket {
	t.Helper()

	byTime := make(map[int64]watcher_timeline_repository.Bucket)

	for _, watcherBuckets := range service.takeBuckets(true) {
		for _, bucket := range watcherBuckets {
			byTime[bucket.At.Time().UTC().UnixMilli()] = bucket
		}
	}

	return byTime
}

func bucketAt(
	t *testing.T,
	byTime map[int64]watcher_timeline_repository.Bucket,
	at time.Time,
) watcher_timeline_repository.Bucket {
	t.Helper()

	bucket, ok := byTime[at.UTC().UnixMilli()]

	if !ok {
		t.Fatalf("no bucket at %v", at)
	}

	return bucket
}

func onlyBucket(t *testing.T, service *Service) watcher_timeline_repository.Bucket {
	t.Helper()

	buckets := service.takeBuckets(true)

	if len(buckets) != 1 {
		t.Fatalf("expected one watcher's buckets, got %d", len(buckets))
	}

	for _, watcherBuckets := range buckets {
		if len(watcherBuckets) != 1 {
			t.Fatalf("expected one bucket, got %d", len(watcherBuckets))
		}

		return watcherBuckets[0]
	}

	return watcher_timeline_repository.Bucket{}
}

// A group with no tags must read as having none, not as null: the panel groups on this
// field, and null and [] are different keys.
func TestAGroupWithoutTagsCarriesAnEmptyList(t *testing.T) {
	_, tags := groupSignature(1, "http", nil)

	if tags == nil {
		t.Fatal("expected an empty list of tags, got nil")
	}

	if len(tags) != 0 {
		t.Fatalf("expected no tags, got %v", tags)
	}
}

// A watcher scoped to one service must not be fed another's traces. The matcher itself
// does not look at service ids at all — the split into byService and global is what does
// it, so a mistake there would make every scoped watcher watch everything.
func TestAScopedWatcherIsNotFedAnotherServicesTraces(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1, ServiceIds: []int{3}})

	service.AddTrace(4, "other-service", "http", nil, nil, time.Now().UTC(), true)

	if len(service.takeBuckets(true)) != 0 {
		t.Fatal("a watcher scoped to service 3 collected service 4")
	}

	service.AddTrace(3, "its-own", "http", nil, nil, time.Now().UTC(), true)

	if len(service.takeBuckets(true)) != 1 {
		t.Fatal("a watcher scoped to service 3 did not collect service 3")
	}
}

// The rollup is capped, and the cap used to be first-come. A slow trace of a shape that
// arrived twenty-first was then dropped from the breakdown entirely, which is the one
// trace a slow_traces watcher is looking for.
func TestAFullBucketMakesRoomForASlowerShape(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1})

	at := time.Date(2026, 9, 7, 10, 0, 0, 0, time.UTC)
	quick := 0.1

	for i := 0; i < maxGroups; i++ {
		service.AddTrace(1, "quick", "type-"+strconv.Itoa(i), nil, &quick, at, true)
	}

	// Inside the same bucket on purpose: what is under test is the cap, not the moment a
	// duration is filed under.
	slow := 5.0

	service.AddTrace(1, "the-slow-one", "type-late", nil, &slow, at, true)

	bucket := bucketAt(t, bucketsByTime(t, service), at)

	if len(bucket.Groups) != maxGroups {
		t.Fatalf("expected the cap to hold, got %d groups", len(bucket.Groups))
	}

	for _, group := range bucket.Groups {
		if group.TraceId == "the-slow-one" {
			return
		}
	}

	t.Fatal("the slowest shape was not kept")
}

// The bucket a trace is counted in is the one it started in, and its duration is filed in
// the one it finished in. A trace that runs longer than a bucket therefore leaves a group
// with a count and no duration at all — and in a bucket of nothing but those, ranking the
// cap on durMax alone compares zero with zero, never holds, and leaves the choice to map
// order. The busiest shape of the window was then as likely to go as one seen once.
func TestAFullBucketOfUnfinishedTracesKeepsTheBusiestShape(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1})

	at := time.Date(2026, 9, 7, 10, 0, 0, 0, time.UTC)

	for i := 0; i < 100; i++ {
		service.AddTrace(1, "hot-"+strconv.Itoa(i), "type-hot", nil, nil, at, true)
	}

	// Every one of these is a shape of its own, so each arrival past the cap evicts.
	for i := 0; i < maxGroups*2; i++ {
		service.AddTrace(1, "cold-"+strconv.Itoa(i), "type-cold-"+strconv.Itoa(i), nil, nil, at, true)
	}

	bucket := bucketAt(t, bucketsByTime(t, service), at)

	if len(bucket.Groups) != maxGroups {
		t.Fatalf("expected the cap to hold, got %d groups", len(bucket.Groups))
	}

	for _, group := range bucket.Groups {
		if group.Type == "type-hot" {
			if group.Count != 100 {
				t.Fatalf("expected the busiest shape to keep its 100 traces, got %d", group.Count)
			}

			return
		}
	}

	t.Fatal("the busiest shape was evicted")
}

// One pass can collect more than the line holds — a backlog being caught up hands over
// the buckets of hours of traces at once — and all of them go into a single $push per
// watcher. Past 16MB the driver refuses that update, and the buckets have already left
// memory, so the flush is lost for every watcher rather than for the oversized one.
func TestAFlushHandsOverNoMoreThanTheLineHolds(t *testing.T) {
	service := newTestService(1, watcher_repository.Match{Version: 1})

	at := time.Date(2026, 9, 7, 10, 0, 0, 0, time.UTC)

	total := watcher_timeline_repository.MaxBuckets + 50

	for i := 0; i < total; i++ {
		service.AddTrace(1, "trace", "http", nil, nil, at.Add(time.Duration(i)*BucketSize), true)
	}

	buckets := service.takeBuckets(true)[1]

	if len(buckets) != watcher_timeline_repository.MaxBuckets {
		t.Fatalf("expected %d buckets, got %d", watcher_timeline_repository.MaxBuckets, len(buckets))
	}

	// The newest are the ones kept: the oldest are what $slice would have dropped anyway.
	oldest := at.Add(time.Duration(total-watcher_timeline_repository.MaxBuckets) * BucketSize)

	for _, bucket := range buckets {
		if bucket.At.Time().UTC().Before(oldest) {
			t.Fatalf("kept a bucket at %s, older than %s", bucket.At.Time().UTC(), oldest)
		}
	}
}

// Two watchers with the same filter are compiled into one matcher carrying both ids, and
// a fan-out that lost one of them would leave that watcher collecting nothing at all.
func TestTwoWatchersWithTheSameFilterBothCollect(t *testing.T) {
	service := &Service{
		buckets: make(map[bucketKey]*bucketState),
		skipped: make(map[int]int),
	}

	service.compile([]watcher_repository.Watcher{
		{Id: 1, Match: watcher_repository.Match{Version: 1, Types: []string{"http"}}},
		{Id: 2, Match: watcher_repository.Match{Version: 1, Types: []string{"http"}}},
	})

	service.AddTrace(1, "trace", "http", nil, nil, time.Now().UTC(), true)

	buckets := service.takeBuckets(true)

	if len(buckets[1]) != 1 || len(buckets[2]) != 1 {
		t.Fatalf("expected both watchers to collect, got %d and %d", len(buckets[1]), len(buckets[2]))
	}
}

// The choice itself, without the map order the test above still depends on: two groups
// that are equally slow — which is every group of a bucket whose traces have not finished
// — are separated by how many traces they hold, not by whichever the runtime offered first.
func TestTheCapSeparatesEquallySlowGroupsByCount(t *testing.T) {
	bucket := &bucketState{
		groups: map[string]*groupState{
			"busy":  {count: 100},
			"quiet": {count: 1},
		},
	}

	evictFastestGroup(bucket)

	if _, ok := bucket.groups["busy"]; !ok {
		t.Fatal("the busiest of two equally slow groups was evicted")
	}

	if _, ok := bucket.groups["quiet"]; ok {
		t.Fatal("the quiet group should have made way")
	}
}
