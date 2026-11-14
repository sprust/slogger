package deps

import (
	"slogger_receiver/internal/config"
	"slogger_receiver/internal/modules/service/internal/repository"
	"slogger_receiver/internal/platform/mysql"
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
