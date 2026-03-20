package socket_server

import (
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"log/slog"
	"net"
	"slogger_receiver/cmd/receiver/socket_server/transport"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/services/buffer_service"
	"slogger_receiver/internal/services/service_service"
	"slogger_receiver/pkg/foundation/errs"
	"sync/atomic"
	"time"
)

// TODO: graceful shutdown. check activeHandlingCount dont work.

type Server struct {
	servContext            context.Context
	servCancel             context.CancelFunc
	network                string
	address                string
	listener               net.Listener
	bufferService          *buffer_service.Service
	serviceService         *service_service.Service
	totalConnectionsCount  atomic.Uint64
	activeConnectionsCount atomic.Int64
	totalHandlingCount     atomic.Uint64
	activeHandlingCount    atomic.Int64
	closing                atomic.Bool
}

type Stats struct {
	Connections struct {
		Total  uint64
		Active int64
	}
	Handling struct {
		Total  uint64
		Active int64
	}
}

func New(network string, address string) *Server {
	ctx, cancel := context.WithCancel(context.Background())

	return &Server{
		servContext:            ctx,
		servCancel:             cancel,
		network:                network,
		address:                address,
		bufferService:          buffer_service.Get(),
		serviceService:         service_service.Get(),
		totalConnectionsCount:  atomic.Uint64{},
		activeConnectionsCount: atomic.Int64{},
		totalHandlingCount:     atomic.Uint64{},
		activeHandlingCount:    atomic.Int64{},
	}
}

func (s *Server) Run(ctx context.Context) error {
	listener, err := net.Listen(s.network, s.address)

	if err != nil {
		return errs.Err(err)
	}

	slog.Info("Socket server started on " + s.network + "//" + s.address)

	s.listener = listener

	go func() {
		select {
		case <-ctx.Done():
			slog.Warn("Shutting down [socket server] by context")

			s.stop()
		}
	}()

	for {
		conn, err := listener.Accept()

		if err != nil {
			if s.closing.Load() {
				break
			}

			var ne net.Error

			if errors.As(err, &ne) && ne.Temporary() {
				continue
			}

			return errs.Err(err)
		}

		s.totalConnectionsCount.Add(1)
		s.activeConnectionsCount.Add(1)

		go func() {
			defer s.activeConnectionsCount.Add(-1)

			err := s.handleConnection(conn)

			if err != nil {
				slog.Error(err.Error())
			}
		}()
	}

	for s.closing.Load() {
		time.Sleep(1 * time.Second)
	}

	s.closing.Store(false)

	return nil
}

func (s *Server) handleConnection(conn net.Conn) error {
	tr := transport.NewTransport(conn)

	defer func(tr *transport.Transport) {
		_ = tr.Close()
	}(tr)

	if s.closing.Load() {
		err := tr.Write("server_is_closing")

		if err != nil {
			return errs.Err(err)
		}

		return nil
	}

	authPayload, err := tr.Read()

	var authMsg dto.AuthMessage

	err = json.Unmarshal(authPayload, &authMsg)

	if err != nil {
		err = tr.Write("cant parse auth message: " + err.Error())

		return errs.Err(err)
	}

	serviceId, err := s.serviceService.GetIdByApiToken(s.servContext, authMsg.ApiToken)

	if err != nil {
		err = tr.Write("error at service searching: " + err.Error())

		return errs.Err(err)
	}

	err = tr.Write("ok")

	if err != nil {
		return errs.Err(err)
	}

	for {
		message, err := tr.Read()

		if err != nil {
			return errs.Err(err)
		}

		if message == nil {
			return nil
		}

		slog.Debug(fmt.Sprintf("received message with len %d", len(message)))

		if s.closing.Load() {
			slog.Debug("closing socket server by request. message skipped.")

			err = tr.Write("server_is_closing")

			if err != nil {
				return errs.Err(err)
			}

			continue
		}

		s.totalHandlingCount.Add(1)
		s.activeHandlingCount.Add(1)

		go func(msg []byte, serviceId int) {
			defer s.activeHandlingCount.Add(-1)

			var tracesMsg dto.TracesMessage

			if err := json.Unmarshal(msg, &tracesMsg); err != nil {
				slog.Error(errs.Err(err).Error())

				return
			}

			if err := s.bufferService.Save(s.servContext, serviceId, &tracesMsg); err != nil {
				slog.Error(errs.Err(err).Error())
			}
		}(message, serviceId)

		err = tr.Write("received")

		if err != nil {
			return errs.Err(err)
		}
	}
}

func (s *Server) GetStats() Stats {
	return Stats{
		Connections: struct {
			Total  uint64
			Active int64
		}{
			Total:  s.totalConnectionsCount.Load(),
			Active: s.activeConnectionsCount.Load(),
		},
		Handling: struct {
			Total  uint64
			Active int64
		}{
			Total:  s.totalHandlingCount.Load(),
			Active: s.activeHandlingCount.Load(),
		},
	}
}

func (s *Server) stop() {
	s.closing.Store(true)
	defer s.closing.Store(false)

	for {
		activeHandlingCount := s.activeHandlingCount.Load()

		if activeHandlingCount > 0 {
			slog.Warn(fmt.Sprintf("Waiting for [%d] handling to finish...", activeHandlingCount))

			time.Sleep(1 * time.Second)
		} else {
			break
		}
	}

	if s.listener != nil {
		_ = s.listener.Close()
	}

	s.servCancel()

	_ = s.serviceService.Close()

	slog.Warn("Socket server stopped")
}
