package find_by_token_action

import (
	"context"
	"slogger_receiver/internal/modules/service/errors"
	"slogger_receiver/internal/modules/service/internal/repository"
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
	repository *repository.MysqlServiceRepository

	cache      map[string]cacheEntry
	cacheMutex sync.RWMutex
}

func Get() *Action {
	once.Do(func() {
		instance = &Action{
			repository: repository.Get(),
			cache:      make(map[string]cacheEntry),
		}
	})

	return instance
}

func (a *Action) Handle(ctx context.Context, token string) (int, errors.ServiceError) {
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
