package periodic_trace_service

import (
	"context"
	"encoding/json"
	"log/slog"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/helpers/datetime_helper"
	"slogger_receiver/internal/repositories/clickhouse_trace_repository"
	"slogger_receiver/internal/repositories/pending_trace_repository"
	"slogger_receiver/internal/services/trace_metric_service"
	"slogger_receiver/internal/services/watcher_service"
	"slogger_receiver/pkg/foundation/errs"
	"strconv"
	"sync"
	"time"
)

// unknownTraceType is what a trace is stored as until its create arrives. An updating
// message carries no type, so a trace whose update is persisted first spends a while
// under this placeholder.
const unknownTraceType = "__UNKNOWN"

// traceStore is the traces table: the write of merged traces, and the read of a stored
// trace when nothing is pending for it.
type traceStore interface {
	FindExisting(ctx context.Context, keys []clickhouse_trace_repository.Key) (map[clickhouse_trace_repository.Key]clickhouse_trace_repository.StoredTrace, error)
	Insert(ctx context.Context, rows []clickhouse_trace_repository.Row) error
}

// pendingStore holds the traces waiting for their other half.
type pendingStore interface {
	FindMany(ctx context.Context, ids []string) (map[string]pending_trace_repository.PendingTrace, error)
	Apply(ctx context.Context, save []pending_trace_repository.PendingTrace, forget []string, now time.Time) error
}

var instance *Service
var once sync.Once

func Get() *Service {
	once.Do(func() {
		instance = New(clickhouse_trace_repository.Get(), pending_trace_repository.Get())
	})

	return instance
}

func New(store traceStore, pending pendingStore) *Service {
	return &Service{store: store, pending: pending}
}

type Service struct {
	store   traceStore
	pending pendingStore
}

// Result is what a batch came to: how many traces were written, and which were not.
type Result struct {
	Saved int
	// Failed holds, per service, the ids of the traces that could not be merged. The rest
	// of the batch was written without them.
	Failed map[int]map[string]bool
}

type batchTrace struct {
	serviceId int
	traceId   string
	traces    *dto.Traces
	key       clickhouse_trace_repository.Key
	id        string
	loggedAt  time.Time
}

// Save merges and writes every trace of a batch.
//
// The other half of a trace is looked for among the pending traces in MongoDB, not in
// ClickHouse: a point read by id, where a read of the traces table has to go through
// FINAL and every granule a key can be in. Most traces never need it — a create sent with
// isP false or one that came with its update is final as it is and goes straight to the insert.
//
//   - a trace that is final (a type and either its update or a create with isP false) is
//     inserted; one that had an update leaves a done mark in its place, so that its create
//     arriving again is dropped as a repeat instead of reopening the trace;
//   - any other create is still waiting for its update: it is inserted, so that it is seen
//     in progress, and kept pending;
//   - an update without its create is only kept pending: it has no type or tags to be
//     shown with.
//
// Only an update that finds nothing pending, or only the done mark, reads ClickHouse: one
// whose trace waited longer than the pending traces live, or came complete already.
//
// A trace written before it was complete has two rows until their parts merge, and the
// table keeps the one with the latest uat. Nothing deletes the older one: a lightweight
// DELETE per batch is a mutation per batch, and under a steady stream they took every
// thread of the background pool, left none to the merges, and the parts piled up. Reads
// go through FINAL, and the hourly OPTIMIZE … FINAL of a closed hour merges it into one
// part.
//
// An error means the batch is to be tried again. Inserting a trace twice does no harm for
// the same reason.
func (s *Service) Save(ctx context.Context, batch map[int]*dto.ServiceTraces) (Result, error) {
	result := Result{Failed: map[int]map[string]bool{}}

	items := make([]batchTrace, 0)
	ids := make([]string, 0)

	for serviceId, serviceTraces := range batch {
		for traceId, traces := range serviceTraces.Items() {
			loggedAt, ok := loggedAtOf(traces)

			if !ok {
				slog.Error("trace " + traceId + " of service " + strconv.Itoa(serviceId) + " has neither a create nor an update")

				markFailed(result.Failed, serviceId, traceId)

				continue
			}

			item := batchTrace{
				serviceId: serviceId,
				traceId:   traceId,
				traces:    traces,
				key: clickhouse_trace_repository.Key{
					ServiceId:     serviceId,
					LoggedAtMicro: loggedAt.UnixMicro(),
					TraceId:       traceId,
				},
				id:       pending_trace_repository.Id(serviceId, traceId),
				loggedAt: loggedAt,
			}

			items = append(items, item)
			ids = append(ids, item.id)
		}
	}

	if len(items) == 0 {
		return result, nil
	}

	pendingTraces, err := s.pending.FindMany(ctx, ids)

	if err != nil {
		return Result{}, errs.Err(err)
	}

	fallbackKeys := make([]clickhouse_trace_repository.Key, 0)
	merging := make([]batchTrace, 0, len(items))

	for _, item := range items {
		if pendingTrace, found := pendingTraces[item.id]; found && pendingTrace.Done {
			// The trace came complete already: its create is a repeat, and only an update
			// still has anything to say. Written again as a new trace, a repeated started
			// create would take the place of the finished one.
			if item.traces.Updating == nil {
				continue
			}

			item.traces = &dto.Traces{Updating: item.traces.Updating, Ids: item.traces.Ids}

			delete(pendingTraces, item.id)
		}

		if _, found := pendingTraces[item.id]; !found && item.traces.Creating == nil {
			fallbackKeys = append(fallbackKeys, item.key)
		}

		merging = append(merging, item)
	}

	existing, err := s.store.FindExisting(ctx, fallbackKeys)

	if err != nil {
		return Result{}, errs.Err(err)
	}

	now := time.Now().UTC().Truncate(time.Microsecond)

	merged := make([]mergedTrace, 0, len(merging))
	rows := make([]clickhouse_trace_repository.Row, 0, len(merging))
	save := make([]pending_trace_repository.PendingTrace, 0)
	forget := make([]string, 0)

	for _, item := range merging {
		var stored *clickhouse_trace_repository.StoredTrace

		hasUpdate := item.traces.Updating != nil
		hasCreate := item.traces.Creating != nil
		pendingTrace, isPending := pendingTraces[item.id]

		if isPending {
			value := storedFromPending(pendingTrace)
			stored = &value
			hasUpdate = hasUpdate || pendingTrace.HasUpdate
			// a pending trace without an update came from its create
			hasCreate = hasCreate || !pendingTrace.HasUpdate
		} else if value, found := existing[item.key]; found {
			stored = &value
			hasCreate = true
		}

		trace := mergeTrace(item.serviceId, item.traceId, item.traces, stored, item.loggedAt, now)

		// An update with no create yet has no type or tags to be shown with. A create that
		// brought no type is written under the placeholder all the same.
		if trace.row.Type == unknownTraceType && !hasCreate {
			save = append(save, pendingFromRow(trace.row, item.loggedAt, true, now))

			continue
		}

		merged = append(merged, trace)
		rows = append(rows, trace.row)

		if hasUpdate {
			save = append(save, pending_trace_repository.PendingTrace{ServiceId: item.serviceId, TraceId: item.traceId, Done: true})

			continue
		}

		if !waitsForUpdate(item.traces.Creating) {
			if isPending {
				forget = append(forget, item.id)
			}

			continue
		}

		save = append(save, pendingFromRow(trace.row, item.loggedAt, false, now))
	}

	if err := s.store.Insert(ctx, rows); err != nil {
		return Result{}, errs.Err(err)
	}

	if err := s.pending.Apply(ctx, save, forget, now); err != nil {
		return Result{}, errs.Err(err)
	}

	for _, item := range merged {
		report(item, now)
	}

	result.Saved = len(items)

	return result, nil
}

// waitsForUpdate says whether a trace written without its update is kept pending for it.
// Only a create with isP false says that no update follows; the status means nothing here,
// so a create that says nothing waits.
func waitsForUpdate(creating *dto.TraceCreating) bool {
	return creating == nil || creating.IsParent == nil || *creating.IsParent
}

// storedFromPending is a pending trace as the other half of a merge.
func storedFromPending(trace pending_trace_repository.PendingTrace) clickhouse_trace_repository.StoredTrace {
	return clickhouse_trace_repository.StoredTrace{
		ParentTraceId: trace.ParentTraceId,
		Type:          trace.Type,
		Status:        trace.Status,
		Tags:          trace.Tags,
		RawData:       trace.RawData,
		Duration:      trace.Duration,
		Memory:        trace.Memory,
		Cpu:           trace.Cpu,
		Pid:           trace.Pid,
		CreatedAt:     time.UnixMicro(trace.CreatedAtMicro).UTC(),
	}
}

// pendingFromRow is what is kept of a merged trace while it waits for its other half.
func pendingFromRow(
	row clickhouse_trace_repository.Row,
	loggedAt time.Time,
	hasUpdate bool,
	now time.Time,
) pending_trace_repository.PendingTrace {
	createdAt, err := time.Parse(clickhouse_trace_repository.TimeLayout, row.CreatedAt)

	if err != nil {
		createdAt = now
	}

	return pending_trace_repository.PendingTrace{
		ServiceId:      row.ServiceId,
		TraceId:        row.TraceId,
		LoggedAtMicro:  loggedAt.UnixMicro(),
		ParentTraceId:  row.ParentTraceId,
		Type:           row.Type,
		Status:         row.Status,
		Tags:           row.Tags,
		RawData:        row.RawData,
		Duration:       row.Duration,
		Memory:         row.Memory,
		Cpu:            row.Cpu,
		Pid:            row.Pid,
		HasUpdate:      hasUpdate,
		CreatedAtMicro: createdAt.UnixMicro(),
	}
}

// mergedTrace is a trace ready to be written, together with what the watchers and the
// trace metrics are told about it once it is.
type mergedTrace struct {
	row         clickhouse_trace_repository.Row
	loggedAt    time.Time
	tags        []string
	countsAsNew bool
	newDuration *float64
	receivedAt  time.Time
}

// mergeTrace puts together what the batch brought of a trace and what is stored of it.
// stored is nil for a trace written for the first time.
//
// The order each field is taken in is the one README "Trace message format" describes:
// the update wins, then what is stored, then the create — except the type, which only
// a create carries, and the parent, which is never changed once stored.
func mergeTrace(
	serviceId int,
	traceId string,
	traces *dto.Traces,
	stored *clickhouse_trace_repository.StoredTrace,
	loggedAt time.Time,
	now time.Time,
) mergedTrace {
	isNewTrace := stored == nil

	if isNewTrace {
		stored = &clickhouse_trace_repository.StoredTrace{}
	}

	parentTraceId := ""

	if stored.ParentTraceId != "" {
		parentTraceId = stored.ParentTraceId
	} else if traces.Creating != nil && traces.Creating.ParentTraceId != nil {
		parentTraceId = *traces.Creating.ParentTraceId
	}

	traceType := unknownTraceType
	if traces.Creating != nil && traces.Creating.Type != "" {
		traceType = traces.Creating.Type
	} else if stored.Type != "" {
		traceType = stored.Type
	}

	status := ""
	if traces.Updating != nil {
		status = traces.Updating.Status
	} else if !isNewTrace {
		status = stored.Status
	} else if traces.Creating != nil {
		status = traces.Creating.Status
	}

	tags := []string{}
	if traces.Updating != nil && traces.Updating.Tags != nil {
		tags = convertTags(*traces.Updating.Tags)
	} else if len(stored.Tags) > 0 {
		tags = stored.Tags
	} else if traces.Creating != nil && len(traces.Creating.Tags) > 0 {
		tags = convertTags(traces.Creating.Tags)
	}

	var updatingData json.RawMessage
	if traces.Updating != nil {
		updatingData = traces.Updating.Data.Raw
	}

	var creatingData json.RawMessage
	if traces.Creating != nil {
		creatingData = traces.Creating.Data.Raw
	}

	data := mergeData(updatingData, json.RawMessage(stored.RawData), creatingData)

	// By value: a trace stored before its numbers arrived holds nulls under them, and a
	// null taken for a stored value would win over the create finally bringing the real one.
	var duration *float64
	if traces.Updating != nil && traces.Updating.Duration != nil {
		duration = traces.Updating.Duration
	} else if stored.Duration != nil {
		duration = stored.Duration
	} else if traces.Creating != nil && traces.Creating.Duration != nil {
		duration = traces.Creating.Duration
	}

	var memory *float64
	if traces.Updating != nil && traces.Updating.Memory != nil {
		memory = traces.Updating.Memory
	} else if stored.Memory != nil {
		memory = stored.Memory
	} else if traces.Creating != nil && traces.Creating.Memory != nil {
		memory = traces.Creating.Memory
	}

	var cpu *float64
	if traces.Updating != nil && traces.Updating.Cpu != nil {
		cpu = traces.Updating.Cpu
	} else if stored.Cpu != nil {
		cpu = stored.Cpu
	} else if traces.Creating != nil && traces.Creating.Cpu != nil {
		cpu = traces.Creating.Cpu
	}

	// Only a create carries the pid.
	var pid *uint32
	if traces.Creating != nil && traces.Creating.Pid != nil {
		pid = traces.Creating.Pid
	} else if stored.Pid != nil {
		pid = stored.Pid
	}

	// The JSON column holds objects only; data of any other shape is kept in dt_raw alone,
	// where it is shown, and no data filter can find it.
	objectData := json.RawMessage("{}")

	if dto.IsObjectJson(data) {
		objectData = data
	}

	createdAt := now

	if !isNewTrace {
		createdAt = stored.CreatedAt
	}

	// Which write counts the trace, and which write brings its duration.
	//
	// Not simply the first write and every write after it. A trace can be written by its
	// update before its create arrives (README, "Trace timeline"), and that first write
	// knows neither the type nor the tags — counting it would file the trace under
	// __UNKNOWN with no tags, where no filtered watcher can ever see it, and the create
	// that follows could not correct it. So the counting write is the one that first gives
	// the trace a type, whichever of the two that turns out to be.
	typeWasKnown := isKnownTraceType(stored.Type)
	typeIsKnown := traceType != unknownTraceType

	var newDuration *float64

	if reportsDuration(typeIsKnown, typeWasKnown, stored.Duration != nil) {
		newDuration = duration
	}

	var receivedAt time.Time

	if traces.Creating != nil {
		receivedAt = traces.Creating.ReceivedAt
	}

	return mergedTrace{
		row: clickhouse_trace_repository.Row{
			ServiceId:     serviceId,
			TraceId:       traceId,
			ParentTraceId: parentTraceId,
			Type:          traceType,
			Status:        status,
			Tags:          tags,
			Data:          objectData,
			RawData:       string(data),
			Duration:      duration,
			Memory:        memory,
			Cpu:           cpu,
			Pid:           pid,
			LoggedAt:      loggedAt.Format(clickhouse_trace_repository.TimeLayout),
			CreatedAt:     createdAt.Format(clickhouse_trace_repository.TimeLayout),
			UpdatedAt:     now.Format(clickhouse_trace_repository.TimeLayout),
		},
		loggedAt:    loggedAt,
		tags:        tags,
		countsAsNew: typeIsKnown && !typeWasKnown,
		newDuration: newDuration,
		receivedAt:  receivedAt,
	}
}

// report hands a written trace to the watchers and the trace metrics.
//
// From here rather than from the socket server because this is the only point that has
// the whole trace: an updating message carries no type, and the merge has just restored
// it. And only after the insert, so that a batch tried again is not counted twice.
func report(item mergedTrace, now time.Time) {
	watcher_service.Get().AddTrace(
		item.row.ServiceId,
		item.row.TraceId,
		item.row.Type,
		item.tags,
		item.row.Status,
		item.newDuration,
		item.loggedAt,
		item.countsAsNew,
	)

	if item.countsAsNew {
		trace_metric_service.Get().AddTrace(
			item.row.ServiceId,
			item.row.Type,
			item.loggedAt,
			item.receivedAt,
			now,
		)
	}
}

// loggedAtOf is the moment a trace is filed under: the create's, or the update's copy of
// it when the update comes first. Both must give the same moment for the two halves to
// meet under one key.
func loggedAtOf(traces *dto.Traces) (time.Time, bool) {
	if traces.Creating != nil {
		return datetime_helper.ConvertLoggedAt(traces.Creating.LoggedAt), true
	}

	if traces.Updating != nil {
		return datetime_helper.ConvertLoggedAt(traces.Updating.ParentLoggedAt), true
	}

	return time.Time{}, false
}

func markFailed(failed map[int]map[string]bool, serviceId int, traceId string) {
	if failed[serviceId] == nil {
		failed[serviceId] = map[string]bool{}
	}

	failed[serviceId][traceId] = true
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
func isKnownTraceType(stored string) bool {
	return stored != "" && stored != unknownTraceType
}

// mergeData picks the data a trace is written with: the update's, else what is stored
// unless it is empty, else the create's.
func mergeData(updating json.RawMessage, existing json.RawMessage, creating json.RawMessage) json.RawMessage {
	if !dto.IsNullJson(updating) {
		return updating
	}

	if !dto.IsEmptyJson(existing) {
		return existing
	}

	if !dto.IsNullJson(creating) {
		return creating
	}

	if !dto.IsNullJson(existing) {
		return existing
	}

	return json.RawMessage("[]")
}

// convertTags keeps the string tags of a message; anything else in the list is dropped.
func convertTags(tags []interface{}) []string {
	result := make([]string, 0, len(tags))

	for _, tag := range tags {
		if tagStr, ok := tag.(string); ok {
			result = append(result, tagStr)
		}
	}

	return result
}
