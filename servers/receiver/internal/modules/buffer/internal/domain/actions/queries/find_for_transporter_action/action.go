package find_for_transporter_action

import (
	"context"
	"log/slog"
	"os"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/modules/buffer/entities"
	"slogger_receiver/internal/modules/buffer/errors"
	"slogger_receiver/internal/modules/buffer/internal/repository"
	"strconv"
	"sync"
)

// Larger batches reduce write overhead; the limit bounds memory held during transport.
const defaultBatchSize = 5000

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

var once sync.Once
var instance *Action

type Action struct {
	repository *repository.MongoBufferRepository
	batchSize  int
}

func Get() *Action {
	once.Do(func() {
		instance = &Action{
			repository: repository.Get(),
			batchSize:  batchSizeFromEnv(),
		}
	})

	return instance
}

func (a *Action) Handle(ctx context.Context) (map[int]*dto.ServiceTraces, []entities.InvalidDoc, errors.BufferError) {
	traces, invalid, err := a.repository.FindMany(ctx, a.batchSize)

	if err != nil {
		return nil, nil, errors.Internal(err)
	}

	return traces, invalid, nil
}
