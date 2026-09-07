package main

import (
	"context"
	"encoding/json"
	"log/slog"
	"os"
	"os/signal"
	"runtime"
	"slogger_receiver/cmd/receiver/socket_server"
	"slogger_receiver/cmd/receiver/traces_transporter"
	"slogger_receiver/internal/services/watcher_service"
	"slogger_receiver/pkg/foundation/logging"
	"syscall"
	"time"

	"github.com/joho/godotenv"
)

func init() {
	err := godotenv.Load()

	if err != nil {
		panic(err)
	}
}

func main() {
	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	logger := logging.Init()
	defer func(logger *logging.Logger) {
		err := logger.Close()

		if err != nil {
			panic(err)
		}
	}(logger)

	signals := make(chan os.Signal, 4)
	defer signal.Stop(signals)

	signal.Notify(signals, os.Interrupt, syscall.SIGTERM)

	socketPort := os.Getenv("SOCKET_PORT")

	socketServer := socket_server.New("tcp", ":"+socketPort)
	transporterServer := traces_transporter.New()

	done := make(chan error, 2)

	go func(ctx context.Context) {
		done <- socketServer.Run(ctx)
	}(ctx)

	go func(ctx context.Context) {
		done <- transporterServer.Run(ctx)
	}(ctx)

	// The watchers' own loop: keeps their filters current and writes closed buckets out.
	// Not part of `done` — it holds nothing that has to be finished before the process
	// exits, and what it has collected is written by the transporter on its way out.
	go watcher_service.Get().Run(ctx)

	go func() {
		for {
			select {
			case <-ctx.Done():
				return
			default:
				saveStats(socketServer, transporterServer)

				time.Sleep(1 * time.Second)
			}
		}
	}()

	select {
	case err := <-done:
		if err != nil {
			panic(err)
		}

		slog.Warn("Completed successfully")
	case sgn := <-signals:
		switch sgn {
		case syscall.SIGTERM, os.Interrupt:
			if sgn == syscall.SIGTERM {
				slog.Warn("Received stop (SIGTERM) signal")
			} else {
				slog.Warn("Received interrupt signal (Ctrl+C)")
			}

			cancel()

			if waitForShutdown(done, 2, 10*time.Second) {
				slog.Warn("Completed successfully by signal")
			} else {
				slog.Error("shutdown by timeout")
			}
		}
	}
}

// waitForShutdown waits for the long-running servers to report, and says whether all of
// them did so before the deadline.
//
// All of them, not whichever finishes first. `done` is shared, and returning on the first
// result ends the process while the other server is still unwinding — which used to mean
// a batch cut in half, and now also means the watchers' last buckets never written, since
// the transporter writes those after its loop ends.
func waitForShutdown(done <-chan error, count int, timeout time.Duration) bool {
	deadline := time.After(timeout)

	for i := 0; i < count; i++ {
		select {
		case err := <-done:
			if err != nil {
				panic(err)
			}
		case <-deadline:
			return false
		}
	}

	return true
}

func saveStats(socketServer *socket_server.Server, transporterServer *traces_transporter.Transporter) {
	var mem runtime.MemStats

	runtime.ReadMemStats(&mem)

	numGoroutine := uint64(runtime.NumGoroutine())
	allocMiB := float32(mem.Alloc / 1024 / 1024)
	totalAllocMiB := float32(mem.TotalAlloc / 1024 / 1024)
	sysMiB := float32(mem.Sys / 1024 / 1024)
	numGC := uint64(mem.NumGC)

	statData := struct {
		Date   string
		Memory struct {
			TotalAllocMiB float32
			AllocMiB      float32
			SysMiB        float32
		}
		NumGC        uint64
		NumGoroutine uint64
		Servers      struct {
			Socket      socket_server.Stats
			Transporter traces_transporter.Stats
		}
	}{
		Date: time.Now().Format("2006-01-02 15:04:05.000"),
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
		Servers: struct {
			Socket      socket_server.Stats
			Transporter traces_transporter.Stats
		}{
			Socket:      socketServer.GetStats(),
			Transporter: transporterServer.GetStats(),
		},
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
