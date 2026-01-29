package buffer_service

import (
	"context"
	"slogger_receiver/internal/dto"
	"slogger_receiver/pkg/foundation/errs"

	"go.mongodb.org/mongo-driver/bson"
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
	doc := bson.M{
		"op":   "u",
		"sid":  serviceId,
		"tid":  trace.TraceId,
		"st":   trace.Status,
		"dt":   trace.Data,
		"plat": convertLoggedAt(trace.ParentLoggedAt),
		"cat":  now(),
	}

	if trace.Tags != nil {
		doc["tgs"] = trace.Tags
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
