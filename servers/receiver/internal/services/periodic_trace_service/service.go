package periodic_trace_service

import (
	"context"
	"errors"
	"log/slog"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/helpers/datetime_helper"
	"slogger_receiver/internal/services/trace_metric_service"
	"slogger_receiver/internal/services/trace_sharding_service"
	"slogger_receiver/internal/services/watcher_service"
	"slogger_receiver/pkg/foundation/errs"
	"sync"
	"sync/atomic"
	"time"

	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
)

// maxConcurrentSaves bounds trace-saving goroutines so the transporter does not
// monopolize the MongoDB connection pool under a large buffered backlog.
const maxConcurrentSaves = 64

// unknownTraceType is what a trace is stored as until its create arrives. An updating
// message carries no type, so a trace whose update is persisted first spends a while
// under this placeholder.
const unknownTraceType = "__UNKNOWN"

var instance *Service
var once sync.Once

func Get() *Service {
	once.Do(func() {
		instance = &Service{
			saveSemaphore: make(chan struct{}, maxConcurrentSaves),
		}
	})

	return instance
}

type Service struct {
	mColl         *mongo.Collection
	saveSemaphore chan struct{}
}

func (s *Service) Save(ctx context.Context, serviceId int, serviceTraces *dto.ServiceTraces) (int, []string) {
	wg := sync.WaitGroup{}

	counter := atomic.Uint64{}

	var mu sync.Mutex
	failedTraceIds := make([]string, 0)

	for traceId, traces := range serviceTraces.Items() {
		wg.Add(1)

		s.saveSemaphore <- struct{}{}

		go func(serviceId int, traceId string, traces *dto.Traces, counter *atomic.Uint64) {
			defer func() {
				wg.Done()

				<-s.saveSemaphore
			}()

			err := s.saveTraces(ctx, serviceId, traceId, traces)

			if err != nil {
				slog.Error("failed to save traces" + err.Error())

				mu.Lock()
				failedTraceIds = append(failedTraceIds, traceId)
				mu.Unlock()

				return
			}

			counter.Add(1)
		}(serviceId, traceId, traces, &counter)
	}

	wg.Wait()

	return int(counter.Load()), failedTraceIds
}

func (s *Service) saveTraces(ctx context.Context, serviceId int, traceId string, traces *dto.Traces) error {
	var loggedAt primitive.DateTime
	var creatingRawLoggedAt string
	var updatingRawLoggedAt string

	if traces.Creating != nil {
		loggedAt = datetime_helper.ConvertLoggedAt(traces.Creating.LoggedAt)

		if v, ok := traces.Creating.LoggedAt.(string); ok {
			creatingRawLoggedAt = v
		}
	} else if traces.Updating != nil {
		loggedAt = datetime_helper.ConvertLoggedAt(traces.Updating.ParentLoggedAt)

		if v, ok := traces.Updating.ParentLoggedAt.(string); ok {
			updatingRawLoggedAt = v
		}
	}

	if loggedAt == 0 {
		return errs.Err(errors.New("loggedAt cannot be zero"))
	}

	shardingService := trace_sharding_service.Get()

	coll, err := shardingService.InitCollection(ctx, loggedAt)

	if err != nil {
		return errs.Err(err)
	}

	filter := bson.M{
		"sid": serviceId,
		"tid": traceId,
	}

	existsTrace := bson.M{}

	err = coll.FindOne(ctx, filter).Decode(&existsTrace)

	if err != nil && !errors.Is(err, mongo.ErrNoDocuments) {
		return errs.Err(err)
	}

	// Whether this write creates the trace or completes one already there. It is the only
	// place that can tell: a trace passes through here twice, and counting both would
	// double every number the watchers are built on.
	isNewTrace := errors.Is(err, mongo.ErrNoDocuments)

	if isNewTrace {
		existsTrace = bson.M{}
	}

	var parentTraceId interface{}

	if existingParentTraceId, ok := existsTrace["ptid"]; ok && existingParentTraceId != nil && existingParentTraceId != "" {
		parentTraceId = existingParentTraceId
	} else if traces.Creating != nil && traces.Creating.ParentTraceId != nil && *traces.Creating.ParentTraceId != "" {
		parentTraceId = *traces.Creating.ParentTraceId
	}

	traceType := unknownTraceType
	if traces.Creating != nil && traces.Creating.Type != "" {
		traceType = traces.Creating.Type
	} else if existingType, ok := existsTrace["tp"].(string); ok && existingType != "" {
		traceType = existingType
	}

	status := ""
	if traces.Updating != nil {
		status = traces.Updating.Status
	} else if existingStatus, ok := existsTrace["st"].(string); ok {
		status = existingStatus
	} else if traces.Creating != nil {
		status = traces.Creating.Status
	}

	var tags interface{}
	if traces.Updating != nil && traces.Updating.Tags != nil {
		tags = s.convertTags(*traces.Updating.Tags)
	} else if existingTags, ok := existsTrace["tgs"]; ok && !isEmptyTags(existingTags) {
		tags = existingTags
	} else if traces.Creating != nil && len(traces.Creating.Tags) > 0 {
		tags = s.convertTags(traces.Creating.Tags)
	} else {
		tags = []interface{}{}
	}

	var data interface{}
	if traces.Updating != nil && traces.Updating.Data != nil {
		data = traces.Updating.Data
	} else if existingData, ok := existsTrace["dt"]; ok {
		data = existingData
	} else if traces.Creating != nil && traces.Creating.Data != nil {
		data = traces.Creating.Data
	} else {
		data = []interface{}{}
	}

	// By value, not by key. The document below is written with every field it has room
	// for, so a trace stored before its numbers arrived carries nulls under them — and a
	// key holding a null, taken for a stored value, wins over the create that is finally
	// bringing the real one. A create landing after an update that carried none would lose
	// its duration, memory and cpu for good.
	var duration interface{}
	if traces.Updating != nil && traces.Updating.Duration != nil {
		duration = *traces.Updating.Duration
	} else if existingDuration := existsTrace["dur"]; existingDuration != nil {
		duration = existingDuration
	} else if traces.Creating != nil && traces.Creating.Duration != nil {
		duration = *traces.Creating.Duration
	}

	var memory interface{}
	if traces.Updating != nil && traces.Updating.Memory != nil {
		memory = *traces.Updating.Memory
	} else if existingMemory := existsTrace["mem"]; existingMemory != nil {
		memory = existingMemory
	} else if traces.Creating != nil && traces.Creating.Memory != nil {
		memory = *traces.Creating.Memory
	}

	var cpu interface{}
	if traces.Updating != nil && traces.Updating.Cpu != nil {
		cpu = *traces.Updating.Cpu
	} else if existingCPU := existsTrace["cpu"]; existingCPU != nil {
		cpu = existingCPU
	} else if traces.Creating != nil && traces.Creating.Cpu != nil {
		cpu = *traces.Creating.Cpu
	}

	timestamps, ok := existsTrace["tss"]
	if !ok || timestamps == nil {
		timestamps = datetime_helper.MakeTimestampsByLoggedAt(loggedAt)
	}

	currentNow := datetime_helper.Now()

	document := bson.M{
		"sid":  serviceId,
		"tid":  traceId,
		"ptid": parentTraceId,
		"tp":   traceType,
		"st":   status,
		"tgs":  tags,
		"dt":   data,
		"dur":  duration,
		"mem":  memory,
		"cpu":  cpu,
		"tss":  timestamps,
		"lat":  loggedAt,
		"hpr":  false,
		"pr":   []interface{}{},
		"uat":  currentNow,
	}

	if creatingRawLoggedAt != "" {
		document["rcLat"] = creatingRawLoggedAt
	}

	if updatingRawLoggedAt != "" {
		document["ucLat"] = updatingRawLoggedAt
	}

	_, err = coll.UpdateOne(
		ctx,
		filter,
		bson.M{
			"$set": document,
			"$setOnInsert": bson.M{
				"cat": currentNow,
			},
		},
		options.Update().SetUpsert(true),
	)

	if err != nil {
		return errs.Err(err)
	}

	// Which write counts the trace, and which write brings its duration.
	//
	// Not simply the first write and every write after it. A trace can be written by its
	// update before its create arrives (README, "Trace timeline"), and that first write
	// knows neither the type nor the tags — counting it would file the trace under
	// __UNKNOWN with no tags, where no filtered watcher can ever see it, and the create
	// that follows could not correct it. So the counting write is the one that first gives
	// the trace a type, whichever of the two that turns out to be.
	typeWasKnown := isKnownTraceType(existsTrace["tp"])

	// By the value, not by the key: the document is written with every field it has room
	// for, so a trace saved before its duration arrived carries `dur` as a null. Read as
	// presence, that null says "already counted" on every write after the first — and then
	// no duration is ever recorded for anybody, which is every slow_traces watcher going
	// quiet for good.
	durationWasStored := durationValue(existsTrace["dur"]) != nil

	typeIsKnown := traceType != unknownTraceType

	countsAsNew := typeIsKnown && !typeWasKnown

	var newDuration interface{}

	if reportsDuration(typeIsKnown, typeWasKnown, durationWasStored) {
		newDuration = duration
	}

	// The watchers are fed from here rather than from the socket server because this is
	// the only point that has the whole trace: an updating message carries no type, and
	// the merge above has just restored it. Nothing is read for it — every value handed
	// over is already in a local variable.
	watcher_service.Get().AddTrace(
		serviceId,
		traceId,
		traceType,
		tagNames(tags),
		durationValue(newDuration),
		loggedAt.Time().UTC(),
		countsAsNew,
	)

	if countsAsNew {
		var receivedAt time.Time

		if traces.Creating != nil {
			receivedAt = traces.Creating.ReceivedAt
		}

		trace_metric_service.Get().AddTrace(
			serviceId,
			traceType,
			loggedAt.Time().UTC(),
			receivedAt,
			currentNow.Time().UTC(),
		)
	}

	slog.Debug("saved trace: " + traceId + " for service: " + string(rune(serviceId)) + " to collection: " + coll.Name())

	return nil
}

// tagNames pulls the tag names out of whichever shape the merge above left them in: the
// list this service just built, or the one decoded from the stored document.
func tagNames(value interface{}) []string {
	var items []interface{}

	switch v := value.(type) {
	case []interface{}:
		items = v
	case primitive.A:
		items = []interface{}(v)
	default:
		return nil
	}

	names := make([]string, 0, len(items))

	for _, item := range items {
		switch tag := item.(type) {
		case primitive.M:
			if name, ok := tag["nm"].(string); ok && name != "" {
				names = append(names, name)
			}
		case primitive.D:
			for _, element := range tag {
				if element.Key != "nm" {
					continue
				}

				if name, ok := element.Value.(string); ok && name != "" {
					names = append(names, name)
				}
			}
		case string:
			if tag != "" {
				names = append(names, tag)
			}
		}
	}

	return names
}

// reportsDuration says whether this write is the one to hand the trace's duration to the
// watchers.
//
// The first write that has both a real type and a duration in hand — which is not always
// the write that brought the duration. Handing it over under the placeholder would file it
// under a type no filter matches and no watcher can see, and the create that follows could
// not put it right: the duration is the merged value by then, so it would look like one
// already reported.
func reportsDuration(typeIsKnown bool, typeWasKnown bool, durationWasStored bool) bool {
	return typeIsKnown && !(durationWasStored && typeWasKnown)
}

// isKnownTraceType says whether what is stored is a real type rather than the placeholder
// a trace wears between its update and its create.
func isKnownTraceType(stored interface{}) bool {
	value, ok := stored.(string)

	return ok && value != "" && value != unknownTraceType
}

// durationValue reads a duration in whatever width it arrived in — the message hands over
// a float, the stored document whatever bson decoded it to.
func durationValue(value interface{}) *float64 {
	switch v := value.(type) {
	case float64:
		return &v
	case float32:
		duration := float64(v)

		return &duration
	case int:
		duration := float64(v)

		return &duration
	case int32:
		duration := float64(v)

		return &duration
	case int64:
		duration := float64(v)

		return &duration
	default:
		return nil
	}
}

// isEmptyTags reports whether a stored tgs value holds no tags, so that an
// empty array written by an out-of-order updating does not shadow the tags
// of a creating that arrives later.
func isEmptyTags(value interface{}) bool {
	switch v := value.(type) {
	case nil:
		return true
	case []interface{}:
		return len(v) == 0
	case primitive.A:
		return len(v) == 0
	default:
		return false
	}
}

func (s *Service) convertTags(tags []interface{}) []interface{} {
	result := make([]interface{}, 0, len(tags))

	for _, tag := range tags {
		if tagStr, ok := tag.(string); ok {
			result = append(result, bson.M{
				"nm": tagStr,
			})
		}
	}

	return result
}
