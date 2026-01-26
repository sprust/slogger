package buffer_service

import (
	"context"
	"errors"
	"slogger_receiver/internal/dto"
	"slogger_receiver/pkg/foundation/errs"
	"time"

	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
)

func (s *Service) insertCreatingTraces(ctx context.Context, serviceId int, traces []dto.TraceCreating) error {
	for _, trace := range traces {
		err := s.insertCreatingTrace(ctx, serviceId, trace)

		if err != nil {
			return errs.Err(err)
		}
	}

	return nil
}

func (s *Service) insertCreatingTrace(ctx context.Context, serviceId int, trace dto.TraceCreating) error {
	timestamp := primitive.NewDateTimeFromTime(time.Now().UTC())

	filter := bson.M{
		"sid": serviceId,
		"tid": trace.TraceId,
	}

	existTrace := bson.M{}

	err := s.mColl.
		FindOne(
			ctx,
			bson.M{
				"sid":   serviceId,
				"tid":   trace.TraceId,
				"__ins": false,
			},
		).
		Decode(&existTrace)

	if err != nil && !errors.Is(err, mongo.ErrNoDocuments) {
		return errs.Err(err)
	}

	hasExistsTrace := len(existTrace) > 0

	profiling := bson.M{}

	if hpr, ok := existTrace["hpr"].(bool); ok && hpr {
		profiling["hpr"] = true

		if pr, ok := existTrace["pr"]; ok {
			profiling["pr"] = pr
		}
	}

	st := chooseTruthy(existTrace["st"], trace.Status)
	tgs := chooseTruthy(existTrace["tgs"], trace.Tags)
	dt := chooseTruthy(existTrace["dt"], trace.Data)
	dur := chooseNonNil(existTrace["dur"], trace.Duration)
	mem := chooseNonNil(existTrace["mem"], trace.Memory)
	cpu := chooseNonNil(existTrace["cpu"], trace.Cpu)

	isParent := false

	if trace.IsParent != nil {
		isParent = *trace.IsParent
	}

	loggedAtMs := int64(trace.LoggedAt)
	loggedAt := primitive.NewDateTimeFromTime(time.Unix(0, loggedAtMs*int64(time.Millisecond)))

	setDoc := bson.M{
		"ptid":   trace.ParentTraceId,
		"tp":     trace.Type,
		"st":     st,
		"tgs":    tgs,
		"dt":     dt,
		"dur":    dur,
		"mem":    mem,
		"cpu":    cpu,
		"lat":    loggedAt,
		"uat":    timestamp,
		"cat":    timestamp,
		"__ins":  true,
		"__upd":  hasExistsTrace || !isParent,
		"__hand": false,
	}

	for key, value := range profiling {
		setDoc[key] = value
	}

	_, err = s.mColl.UpdateOne(
		ctx,
		filter,
		bson.M{"$set": setDoc},
		options.Update().SetUpsert(true),
	)

	if err != nil {
		return errs.Err(err)
	}

	return nil
}
