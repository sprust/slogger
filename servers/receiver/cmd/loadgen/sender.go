package main

import (
	"encoding/binary"
	"encoding/json"
	"fmt"
	"io"
	"net"
	"time"
)

// sender speaks the receiver's socket protocol the way SocketClient of slogger/laravel
// does: a big-endian length prefix before every frame, the token first, then one
// {"c": "<json>", "u": "<json>"} frame per batch answered by "received".
type sender struct {
	address string
	token   string
	timeout time.Duration
	conn    net.Conn
}

func newSender(address string, token string, timeout time.Duration) *sender {
	return &sender{address: address, token: token, timeout: timeout}
}

func (s *sender) connect() error {
	s.close()

	conn, err := net.DialTimeout("tcp", s.address, s.timeout)

	if err != nil {
		return err
	}

	s.conn = conn

	auth, err := json.Marshal(map[string]string{"t": s.token})

	if err != nil {
		return err
	}

	response, err := s.exchange(auth)

	if err != nil {
		s.close()

		return fmt.Errorf("auth: %w", err)
	}

	if response != "ok" {
		s.close()

		return fmt.Errorf("auth: unexpected response %q", response)
	}

	return nil
}

func (s *sender) close() {
	if s.conn != nil {
		_ = s.conn.Close()

		s.conn = nil
	}
}

// send writes one batch and waits for its answer. A failed exchange drops the connection,
// since a half-written frame leaves the stream out of step.
func (s *sender) send(creating []traceCreating, updating []traceUpdating) error {
	payload := map[string]string{}

	if len(creating) > 0 {
		encoded, err := json.Marshal(creating)

		if err != nil {
			return err
		}

		payload["c"] = string(encoded)
	}

	if len(updating) > 0 {
		encoded, err := json.Marshal(updating)

		if err != nil {
			return err
		}

		payload["u"] = string(encoded)
	}

	if len(payload) == 0 {
		return nil
	}

	frame, err := json.Marshal(payload)

	if err != nil {
		return err
	}

	if s.conn == nil {
		if err := s.connect(); err != nil {
			return err
		}
	}

	response, err := s.exchange(frame)

	if err != nil {
		s.close()

		return err
	}

	if response != "received" {
		s.close()

		return fmt.Errorf("unexpected response %q", response)
	}

	return nil
}

func (s *sender) exchange(frame []byte) (string, error) {
	if err := s.conn.SetDeadline(time.Now().Add(s.timeout)); err != nil {
		return "", err
	}

	header := make([]byte, 4)

	binary.BigEndian.PutUint32(header, uint32(len(frame)))

	if _, err := s.conn.Write(append(header, frame...)); err != nil {
		return "", err
	}

	if _, err := io.ReadFull(s.conn, header); err != nil {
		return "", err
	}

	response := make([]byte, binary.BigEndian.Uint32(header))

	if _, err := io.ReadFull(s.conn, response); err != nil {
		return "", err
	}

	return string(response), nil
}
