package periodic_trace_service

import (
	"context"
	"errors"
	"log/slog"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/helpers/datetime_helper"
	"slogger_receiver/internal/services/trace_sharding_service"
	"slogger_receiver/pkg/foundation/errs"
	"sync"
	"sync/atomic"

	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
)

var instance *Service
var once sync.Once

func Get() *Service {
	once.Do(func() {
		instance = &Service{}
	})

	return instance
}

type Service struct {
	mColl *mongo.Collection
}

func (s *Service) Save(ctx context.Context, serviceId int, serviceTraces *dto.ServiceTraces) int {
	wg := sync.WaitGroup{}

	counter := atomic.Uint64{}

	for traceId, traces := range serviceTraces.Items() {
		wg.Add(1)

		go func(serviceId int, traceId string, traces *dto.Traces, counter *atomic.Uint64) {
			defer wg.Done()

			err := s.saveTraces(ctx, serviceId, traceId, traces)

			if err != nil {
				slog.Error("failed to save traces" + err.Error())

				return
			}

			counter.Add(1)
		}(serviceId, traceId, traces, &counter)
	}

	wg.Wait()

	return int(counter.Load())
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

	if errors.Is(err, mongo.ErrNoDocuments) {
		existsTrace = bson.M{}
	}

	var parentTraceId interface{}

	if existingParentTraceId, ok := existsTrace["ptid"]; ok && existingParentTraceId != nil && existingParentTraceId != "" {
		parentTraceId = existingParentTraceId
	} else if traces.Creating != nil && traces.Creating.ParentTraceId != nil && *traces.Creating.ParentTraceId != "" {
		parentTraceId = *traces.Creating.ParentTraceId
	}

	traceType := "__UNKNOWN"
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
	} else if existingTags, ok := existsTrace["tgs"]; ok {
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

	var duration interface{}
	if traces.Updating != nil && traces.Updating.Duration != nil {
		duration = *traces.Updating.Duration
	} else if existingDuration, ok := existsTrace["dur"]; ok {
		duration = existingDuration
	} else if traces.Creating != nil && traces.Creating.Duration != nil {
		duration = *traces.Creating.Duration
	}

	var memory interface{}
	if traces.Updating != nil && traces.Updating.Memory != nil {
		memory = *traces.Updating.Memory
	} else if existingMemory, ok := existsTrace["mem"]; ok {
		memory = existingMemory
	} else if traces.Creating != nil && traces.Creating.Memory != nil {
		memory = *traces.Creating.Memory
	}

	var cpu interface{}
	if traces.Updating != nil && traces.Updating.Cpu != nil {
		cpu = *traces.Updating.Cpu
	} else if existingCPU, ok := existsTrace["cpu"]; ok {
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

	slog.Debug("saved trace: " + traceId + " for service: " + string(rune(serviceId)) + " to collection: " + coll.Name())

	return nil
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
