package buffer_service

import (
	"context"
	"encoding/json"
	"log/slog"
	"os"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/repositories/buffer_repository"
	"slogger_receiver/pkg/foundation/errs"
	"strconv"
	"sync"

	"go.mongodb.org/mongo-driver/bson/primitive"
)

// defaultBatchSize is how many buffer documents the transporter takes per pass: a trace
// each, so also the rows of one INSERT. Each insert is a part ClickHouse has to merge
// later, and fewer, larger ones cost it less than many small ones.
const defaultBatchSize = 5000

var instance *Service
var once sync.Once

func Get() *Service {
	once.Do(func() {
		instance = &Service{
			repository: buffer_repository.Get(),
			batchSize:  batchSizeFromEnv(),
		}
	})

	return instance
}

type Service struct {
	repository *buffer_repository.Repository
	batchSize  int
}

func batchSizeFromEnv() int {
	value := os.Getenv("TRANSPORTER_BATCH_SIZE")

	if value == "" {
		return defaultBatchSize
	}

	size, err := strconv.Atoi(value)

	if err != nil || size <= 0 {
		slog.Error("TRANSPORTER_BATCH_SIZE must be a positive number, got " + value + ", using " + strconv.Itoa(defaultBatchSize))

		return defaultBatchSize
	}

	return size
}

func (s *Service) Save(ctx context.Context, serviceId int, traces *dto.TracesMessage) error {
	if traces.Creating != "" {
		var creating []dto.TraceCreating

		err := json.Unmarshal([]byte(traces.Creating), &creating)

		if err != nil {
			return errs.Err(err)
		}

		err = s.repository.InsertCreatingTraces(ctx, serviceId, creating)

		if err != nil {
			return errs.Err(err)
		}
	}

	if traces.Updating != "" {
		var updating []dto.TraceUpdating

		err := json.Unmarshal([]byte(traces.Updating), &updating)

		if err != nil {
			return errs.Err(err)
		}

		err = s.repository.InsertUpdatingTraces(ctx, serviceId, updating)

		if err != nil {
			return errs.Err(err)
		}
	}

	return nil
}

// FindForTransporter returns a batch to save, and beside it the documents of that batch
// that cannot be saved at all and are to be moved out of the buffer.
func (s *Service) FindForTransporter(ctx context.Context) (map[int]*dto.ServiceTraces, []buffer_repository.InvalidDoc, error) {
	return s.repository.FindMany(ctx, s.batchSize)
}

func (s *Service) MoveToInvalid(ctx context.Context, docs []buffer_repository.InvalidDoc) error {
	return s.repository.MoveToInvalid(ctx, docs)
}

func (s *Service) DeleteByIds(ctx context.Context, ids []primitive.ObjectID) (int64, error) {
	return s.repository.DeleteByIds(ctx, ids)
}

func (s *Service) MarkFailed(ctx context.Context, ids []primitive.ObjectID, maxAttempts int) error {
	return s.repository.MarkFailed(ctx, ids, maxAttempts)
}
