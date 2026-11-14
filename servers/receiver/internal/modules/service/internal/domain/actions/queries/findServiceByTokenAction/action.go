package findServiceByTokenAction

import (
	"context"
	"slogger/internal/modules/service/internal/deps"
	"slogger/internal/modules/service/internal/errors"
	"slogger/internal/modules/service/internal/repository"
	"sync"
	"time"
)

var once sync.Once
var instance *Action

const tokenCacheTtl = 30 * time.Second

type cacheEntry struct {
	serviceId int
	expiresAt time.Time
}

type Action struct {
	repository repository.ServiceRepository

	cache      map[string]cacheEntry
	cacheMutex sync.RWMutex
}

func Get() *Action {
	once.Do(func() {
		instance = New(
			deps.Get().ServiceRepository,
		)
	})

	return instance
}

func New(repository repository.ServiceRepository) *Action {
	return &Action{
		repository: repository,
		cache:      make(map[string]cacheEntry),
	}
}

func (a *Action) Handle(ctx context.Context, token string) (int, errors.Error) {
	a.cacheMutex.RLock()

	if entry, exists := a.cache[token]; exists {
		if time.Now().Before(entry.expiresAt) {
			a.cacheMutex.RUnlock()

			return entry.serviceId, nil
		}
	}

	a.cacheMutex.RUnlock()

	serviceId, err := a.repository.FindIdByToken(ctx, token)

	if err != nil {
		return 0, errors.Internal(err)
	}

	if serviceId == 0 {
		return 0, errors.NotFoundByToken(token)
	}

	a.cacheMutex.Lock()
	defer a.cacheMutex.Unlock()

	a.cache[token] = cacheEntry{
		serviceId: serviceId,
		expiresAt: time.Now().Add(tokenCacheTtl),
	}

	return serviceId, nil
}
