package repository

import (
	"context"
	"slogger_receiver/internal/platform/mysql"
)

type ServiceRepository interface {
	FindIdByToken(ctx context.Context, apiToken string) (int, error)
}

type MysqlServiceRepository struct {
	conn  *mysql.Connection
	table string
}

func NewMysqlServiceRepository(conn *mysql.Connection, table string) *MysqlServiceRepository {
	return &MysqlServiceRepository{
		conn:  conn,
		table: table,
	}
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
