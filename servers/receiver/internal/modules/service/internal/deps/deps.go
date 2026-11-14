package deps

import (
	"slogger/internal/config"
	"slogger/internal/modules/service/internal/repository"
	"slogger/internal/platform/mysql"
	"sync"
)

var once sync.Once
var instance *Deps

type Deps struct {
	ServiceRepository repository.ServiceRepository
}

func Get() *Deps {
	once.Do(func() {
		instance = &Deps{
			ServiceRepository: repository.NewMysqlServiceRepository(
				mysql.Get(),
				config.Get().Mysql().TableServices(),
			),
		}
	})

	return instance
}
