package deps

import (
	"slogger/internal/config"
	"slogger/internal/modules/buffer/internal/repository"
	"slogger/internal/platform/mongodb"
	"sync"
)

var once sync.Once
var instance *Deps

type Deps struct {
	BufferRepository repository.BufferRepository
}

func Get() *Deps {
	once.Do(func() {
		instance = &Deps{
			BufferRepository: repository.NewMongoBufferRepository(
				mongodb.Get(),
				config.Get().Mongodb().CollectionBuffer(),
				config.Get().Mongodb().CollectionInvalidBuffer(),
			),
		}
	})

	return instance
}
