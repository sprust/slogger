package deps

import (
	"slogger/internal/config"
	"slogger/internal/modules/watcher/internal/repository/watcher_repository"
	"slogger/internal/modules/watcher/internal/repository/watcher_timeline_repository"
	"slogger/internal/platform/mongodb"
	"slogger/internal/platform/mysql"
	"sync"
)

var once sync.Once
var instance *Deps

type Deps struct {
	WatcherRepository         watcher_repository.Repository
	WatcherTimelineRepository watcher_timeline_repository.Repository
}

func Get() *Deps {
	once.Do(func() {
		instance = &Deps{
			WatcherRepository: watcher_repository.NewMysqlRepository(
				mysql.Get(),
				config.Get().Mysql().TableWatchers(),
			),
			WatcherTimelineRepository: watcher_timeline_repository.NewMongoRepository(
				mongodb.Get(),
				config.Get().Mongodb().CollectionWatcherTimelines(),
			),
		}
	})

	return instance
}
