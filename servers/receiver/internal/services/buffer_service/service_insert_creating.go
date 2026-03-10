package buffer_service

import (
	"context"
	"slogger_receiver/internal/dto"
	"slogger_receiver/pkg/foundation/errs"

	"go.mongodb.org/mongo-driver/bson"
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
	doc := bson.M{
		"op":  "c",
		"sid": serviceId,
		"tid": trace.TraceId,
		"tp":  trace.Type,
		"st":  trace.Status,
		"tgs": trace.Tags,
		"dt":  trace.Data,
		"lat": convertLoggedAt(trace.LoggedAt),
		"cat": now(),
	}

	if trace.ParentTraceId != nil {
		doc["ptid"] = trace.ParentTraceId
	}

	if trace.Duration != nil {
		doc["dur"] = trace.Duration
	}

	if trace.Memory != nil {
		doc["mem"] = trace.Memory
	}

	if trace.Cpu != nil {
		doc["cpu"] = trace.Cpu
	}

	_, err := s.mColl.InsertOne(ctx, doc)

	if err != nil {
		return errs.Err(err)
	}

	return nil
}
