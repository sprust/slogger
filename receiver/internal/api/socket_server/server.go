package socket_server

import (
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"log/slog"
	"net"
	"slogger_receiver/internal/api/socket_server/transport"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/repositories/service_repository"
	"slogger_receiver/internal/services/buffer_service"
	"slogger_receiver/pkg/foundation/errs"
	"sync/atomic"
)

type Server struct {
	network           string
	address           string
	listener          net.Listener
	bufferService     *buffer_service.Service
	serviceRepository *service_repository.Repository
	closed            atomic.Bool
}

func NewServer(network string, address string) *Server {
	return &Server{
		network:           network,
		address:           address,
		serviceRepository: service_repository.Get(),
		bufferService:     buffer_service.Get(),
	}
}

func (s *Server) Run(ctx context.Context) error {
	listener, err := net.Listen(s.network, s.address)

	if err != nil {
		return errs.Err(err)
	}

	slog.Info("Socket server started on " + s.network + "//" + s.address)

	s.listener = listener

	go func(ctx context.Context, s *Server) {
		select {
		case <-ctx.Done():
			slog.Warn("Shutting down [socket server] by context")

			s.stop()
		}
	}(ctx, s)

	for {
		conn, err := listener.Accept()

		if err != nil {
			if s.closed.Load() {
				break
			}

			var ne net.Error

			if errors.As(err, &ne) && ne.Temporary() {
				continue
			}

			return errs.Err(err)
		}

		go func(ctx context.Context, s *Server, conn net.Conn) {
			err := s.handleConnection(ctx, conn)

			if err != nil {
				slog.Error(err.Error())
			}
		}(ctx, s, conn)
	}

	return nil
}

func (s *Server) stop() {
	s.closed.Store(true)

	if s.listener != nil {
		_ = s.listener.Close()
	}

	_ = s.serviceRepository.Close()
}

func (s *Server) handleConnection(ctx context.Context, conn net.Conn) error {
	tr := transport.NewTransport(conn)

	defer func(tr *transport.Transport) {
		_ = tr.Close()
	}(tr)

	authPayload, err := tr.Read()

	slog.Debug(fmt.Sprintf("received authPayload: %s", authPayload))

	var authMsg dto.AuthMessage

	err = json.Unmarshal(authPayload, &authMsg)

	if err != nil {
		err = tr.Write("cant parse auth message: " + err.Error())

		return errs.Err(err)
	}

	serviceId, err := s.serviceRepository.FindIdByApiToken(ctx, authMsg.ApiToken)

	if err != nil {
		err = tr.Write("error at service searching: " + err.Error())

		return errs.Err(err)
	}

	if serviceId == 0 {
		err = tr.Write("invalid token")

		if err != nil {
			return errs.Err(err)
		}

		return nil
	}

	err = tr.Write("ok")

	if err != nil {
		return errs.Err(err)
	}

	tracesCounter := 0

	defer func() {
		fmt.Println("traces count: ", tracesCounter)
	}()

	for {
		tracePayload, err := tr.Read()

		if err != nil {
			return errs.Err(err)
		}

		if tracePayload == nil {
			slog.Debug("received empty tracePayload, closing connection")

			return nil
		}

		tracesCounter++

		slog.Debug(fmt.Sprintf("received tracePayload: %s", tracePayload))

		var tracesMsg dto.TracesMessage

		err = json.Unmarshal(tracePayload, &tracesMsg)

		if err != nil {
			slog.Error(errs.Err(err).Error())
		}

		err = s.bufferService.Save(ctx, serviceId, &tracesMsg)

		if err != nil {
			slog.Error(errs.Err(err).Error())
		}
	}
}
