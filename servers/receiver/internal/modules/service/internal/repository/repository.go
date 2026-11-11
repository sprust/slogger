package repository

import (
	"context"
	"os"
	"slogger_receiver/internal/platform/mysql"
	"sync"
)

var instance *MysqlServiceRepository
var once sync.Once

type MysqlServiceRepository struct {
	conn  *mysql.Connection
	table string
}

func Get() *MysqlServiceRepository {
	once.Do(func() {
		instance = &MysqlServiceRepository{
			conn:  mysql.Get(),
			table: os.Getenv("MYSQL_TABLE_SERVICES"),
		}
	})

	return instance
}

func (s *MysqlServiceRepository) FindIdByToken(ctx context.Context, apiToken string) (int, error) {
	db, err := s.conn.DB()

	if err != nil {
		return 0, err
	}

	var ids []int

	err = db.SelectContext(
		ctx,
		&ids,
		"SELECT id FROM "+s.table+" WHERE api_token = ? LIMIT 1",
		apiToken,
	)

	if err != nil {
		return 0, err
	}

	if len(ids) == 0 {
		return 0, nil
	}

	serviceId := ids[0]

	return serviceId, nil
}
