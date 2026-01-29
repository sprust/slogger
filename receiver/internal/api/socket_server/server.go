package socket_server

import (
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"log/slog"
	"net"
	"os"
	"runtime"
	"slogger_receiver/internal/api/socket_server/transport"
	"slogger_receiver/internal/dto"
	"slogger_receiver/internal/repositories/service_repository"
	"slogger_receiver/internal/services/buffer_service"
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
	serviceRepository      *service_repository.Repository
	totalConnectionsCount  atomic.Uint64
	activeConnectionsCount atomic.Int64
	totalHandlingCount     atomic.Uint64
	activeHandlingCount    atomic.Int64
	closing                atomic.Bool
}

func NewServer(network string, address string) *Server {
	ctx, cancel := context.WithCancel(context.Background())

	return &Server{
		servContext:            ctx,
		servCancel:             cancel,
		network:                network,
		address:                address,
		bufferService:          buffer_service.Get(),
		serviceRepository:      service_repository.Get(),
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

	go func() {
		for {
			select {
			case <-ctx.Done():
				break
			default:
				s.showStat()

				time.Sleep(1 * time.Second)
			}
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

	serviceId, err := s.serviceRepository.FindIdByApiToken(s.servContext, authMsg.ApiToken)

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

	for {
		tracePayload, err := tr.Read()

		if err != nil {
			return errs.Err(err)
		}

		if tracePayload == nil {
			return nil
		}

		slog.Debug(fmt.Sprintf("received message with len %d", len(tracePayload)))

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

		go func() {
			defer s.activeHandlingCount.Add(-1)

			var tracesMsg dto.TracesMessage

			err = json.Unmarshal(tracePayload, &tracesMsg)

			if err != nil {
				slog.Error(errs.Err(err).Error())
			}

			err = s.bufferService.Save(s.servContext, serviceId, &tracesMsg)

			if err != nil {
				slog.Error(errs.Err(err).Error())
			}
		}()

		err = tr.Write("received")

		if err != nil {
			return errs.Err(err)
		}
	}
}

func (s *Server) showStat() {
	var mem runtime.MemStats

	runtime.ReadMemStats(&mem)

	numGoroutine := uint64(runtime.NumGoroutine())
	allocMiB := float32(mem.Alloc / 1024 / 1024)
	totalAllocMiB := float32(mem.TotalAlloc / 1024 / 1024)
	sysMiB := float32(mem.Sys / 1024 / 1024)
	numGC := uint64(mem.NumGC)

	statData := struct {
		Date        string
		Connections struct {
			Total  uint64
			Active int64
		}
		Handling struct {
			Total  uint64
			Active int64
		}
		Memory struct {
			TotalAllocMiB float32
			AllocMiB      float32
			SysMiB        float32
		}
		NumGC        uint64
		NumGoroutine uint64
	}{
		Date: time.Now().Format("2006-01-02 15:04:05.000"),
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
		Memory: struct {
			TotalAllocMiB float32
			AllocMiB      float32
			SysMiB        float32
		}{
			TotalAllocMiB: totalAllocMiB,
			AllocMiB:      allocMiB,
			SysMiB:        sysMiB,
		},
		NumGC:        numGC,
		NumGoroutine: numGoroutine,
	}

	jsonData, err := json.MarshalIndent(statData, "", "  ")

	if err != nil {
		slog.Error("Failed to marshal stats to JSON: " + err.Error())
		return
	}

	err = os.WriteFile("storage/stats.json", jsonData, 0644)

	if err != nil {
		slog.Error("Failed to write stats to storage/stats.json: " + err.Error())
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

	_ = s.serviceRepository.Close()

	slog.Warn("Socket server stopped")
}
