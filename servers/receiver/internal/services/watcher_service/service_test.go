package watcher_service

import (
	"slogger_receiver/internal/repositories/watcher_repository"
	"slogger_receiver/internal/repositories/watcher_timeline_repository"
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

	bucket := onlyBucket(t, service)

	if bucket.Count != 1 {
		t.Fatalf("expected one trace, got %d", bucket.Count)
	}

	if bucket.DurCount != 1 || bucket.DurMax != 12.5 || bucket.DurSum != 12.5 {
		t.Fatalf("the duration of the finished trace was not recorded: %+v", bucket)
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

	bucket := onlyBucket(t, service)

	if bucket.Groups[0].TraceId != "slow" {
		t.Fatalf("expected the slowest trace to be named, got %q", bucket.Groups[0].TraceId)
	}

	if bucket.Groups[0].DurMax != 30 {
		t.Fatalf("expected the longest duration, got %v", bucket.Groups[0].DurMax)
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
