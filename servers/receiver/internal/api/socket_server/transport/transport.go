package transport

import (
	"encoding/binary"
	"fmt"
	"io"
	"net"
	"slogger_receiver/pkg/foundation/errs"
	"time"
)

const (
	maxPayloadSize = 10 * 1024 * 1024 // 10 mb
	readTimeout    = 30 * time.Second
	writeTimeout   = 30 * time.Second
)

type Transport struct {
	conn net.Conn
}

func NewTransport(conn net.Conn) *Transport {
	return &Transport{
		conn: conn,
	}
}

func (t *Transport) Read() ([]byte, error) {
	sizeBuf := make([]byte, 4)

	if err := t.conn.SetReadDeadline(time.Now().Add(readTimeout)); err != nil {
		return nil, errs.Err(err)
	}

	_, err := io.ReadFull(t.conn, sizeBuf)

	if err != nil {
		if err == io.EOF {
			return nil, nil
		}

		return nil, errs.Err(err)
	}

	size := int(binary.BigEndian.Uint32(sizeBuf))

	if size == 0 {
		return []byte{}, nil
	}

	if size > maxPayloadSize {
		return nil, errs.Err(fmt.Errorf("payload too large: %d > %d", size, maxPayloadSize))
	}

	if err := t.conn.SetReadDeadline(time.Now().Add(readTimeout)); err != nil {
		return nil, errs.Err(err)
	}

	payload := make([]byte, size)
	_, err = io.ReadFull(t.conn, payload)

	if err != nil {
		return nil, errs.Err(err)
	}

	return payload, nil
}

func (t *Transport) Write(payload string) error {
	dataLength := uint32(len(payload))

	lengthBuf := make([]byte, 4)
	binary.BigEndian.PutUint32(lengthBuf, dataLength)

	if err := t.conn.SetWriteDeadline(time.Now().Add(writeTimeout)); err != nil {
		return errs.Err(err)
	}

	if err := writeFull(t.conn, lengthBuf); err != nil {
		return errs.Err(err)
	}

	if err := writeFull(t.conn, []byte(payload)); err != nil {
		return errs.Err(err)
	}

	return nil
}

func writeFull(conn net.Conn, data []byte) error {
	for len(data) > 0 {
		n, err := conn.Write(data)

		if err != nil {
			return err
		}

		data = data[n:]
	}

	return nil
}

func (t *Transport) Close() error {
	if t.conn == nil {
		return nil
	}

	return t.conn.Close()
}
