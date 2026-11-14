package findBufferForTransporterAction

import (
	"context"
	"log/slog"
	"os"
	"slogger/internal/dto"
	"slogger/internal/modules/buffer/entities"
	"slogger/internal/modules/buffer/internal/deps"
	"slogger/internal/modules/buffer/internal/errors"
	"slogger/internal/modules/buffer/internal/repository"
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
	repository repository.BufferRepository
	batchSize  int
}

func Get() *Action {
	once.Do(func() {
		instance = New(
			deps.Get().BufferRepository,
			batchSizeFromEnv(),
		)
	})

	return instance
}

func New(repository repository.BufferRepository, batchSize int) *Action {
	return &Action{
		repository: repository,
		batchSize:  batchSize,
	}
}

func (a *Action) Handle(ctx context.Context) (map[int]*dto.ServiceTraces, []entities.InvalidDoc, errors.Error) {
	traces, invalid, err := a.repository.FindMany(ctx, a.batchSize)

	if err != nil {
		return nil, nil, errors.Internal(err)
	}

	return traces, invalid, nil
}
