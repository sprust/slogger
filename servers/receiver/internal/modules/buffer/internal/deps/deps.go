package deps

import (
	"slogger_receiver/internal/config"
	"slogger_receiver/internal/modules/buffer/internal/repository"
	"slogger_receiver/internal/platform/mongodb"
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
