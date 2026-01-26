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

func (s *Service) insertUpdatingTraces(ctx context.Context, serviceId int, traces []dto.TraceUpdating) error {
	for _, trace := range traces {
		err := s.insertUpdatingTrace(ctx, serviceId, trace)

		if err != nil {
			return errs.Err(err)
		}
	}

	return nil
}

func (s *Service) insertUpdatingTrace(ctx context.Context, serviceId int, trace dto.TraceUpdating) error {
	timestamp := primitive.NewDateTimeFromTime(time.Now().UTC())

	filter := bson.M{
		"sid": serviceId,
		"tid": trace.TraceId,
	}

	existTrace := bson.M{}

	err := s.mColl.FindOne(ctx, filter).Decode(&existTrace)

	if err != nil && !errors.Is(err, mongo.ErrNoDocuments) {
		return errs.Err(err)
	}

	hasExistsTrace := len(existTrace) > 0

	if !hasExistsTrace {
		var tags []interface{}

		if trace.Tags != nil {
			tags = *trace.Tags
		}

		var data interface{} = "{}"
		if trace.Data != nil {
			data = trace.Data
		}

		doc := bson.M{
			"sid":    serviceId,
			"tid":    trace.TraceId,
			"ptid":   nil,
			"tp":     nil,
			"st":     trace.Status,
			"tgs":    tags,
			"dt":     data,
			"dur":    trace.Duration,
			"mem":    trace.Memory,
			"cpu":    trace.Cpu,
			"tss":    bson.M{},
			"lat":    timestamp,
			"uat":    timestamp,
			"cat":    timestamp,
			"__ins":  false,
			"__upd":  true,
			"__hand": false,
		}

		_, err = s.mColl.InsertOne(ctx, doc)
		if err != nil {
			return errs.Err(err)
		}

		return nil
	}

	setDoc := bson.M{
		"st":     trace.Status,
		"uat":    timestamp,
		"__ins":  true,
		"__upd":  true,
		"__hand": false,
	}

	if trace.Tags != nil {
		setDoc["tgs"] = *trace.Tags
	}

	if trace.Data != nil {
		setDoc["dt"] = trace.Data
	}

	if trace.Duration != nil {
		setDoc["dur"] = trace.Duration
	}

	if trace.Memory != nil {
		setDoc["mem"] = trace.Memory
	}

	if trace.Cpu != nil {
		setDoc["cpu"] = trace.Cpu
	}

	_, err = s.mColl.UpdateOne(
		ctx,
		filter,
		bson.M{"$set": setDoc},
		options.Update(),
	)

	if err != nil {
		return errs.Err(err)
	}

	return nil
}
