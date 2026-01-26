package transport

import (
	"encoding/binary"
	"io"
	"net"
	"slogger_receiver/pkg/foundation/errs"
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

	_, err := io.ReadFull(t.conn, sizeBuf)

	if err != nil {
		if err == io.EOF {
			return nil, nil
		}

		return nil, errs.Err(err)
	}

	size := int(sizeBuf[0])<<24 | int(sizeBuf[1])<<16 | int(sizeBuf[2])<<8 | int(sizeBuf[3])

	payload := make([]byte, size)
	bytesRead := 0

	const bufferSize = 32 * 1024 // 32KB buffer
	buffer := make([]byte, bufferSize)
	bytesRemaining := int64(size)

	for bytesRemaining > 0 {
		bytesToRead := int64(bufferSize)

		if bytesToRead > bytesRemaining {
			bytesToRead = bytesRemaining
		}

		readBytes, err := t.conn.Read(buffer[:bytesToRead])

		if err != nil {
			return nil, errs.Err(err)
		}

		copy(payload[bytesRead:], buffer[:readBytes])
		bytesRead += readBytes
		bytesRemaining -= int64(readBytes)
	}

	return payload, nil
}

func (t *Transport) Write(payload string) error {
	dataLength := uint32(len(payload))

	lengthBuf := make([]byte, 4)
	binary.BigEndian.PutUint32(lengthBuf, dataLength)

	_, err := t.conn.Write(
		[]byte(
			string(lengthBuf) + payload,
		),
	)

	if err != nil {
		return errs.Err(err)
	}

	return nil
}

func (t *Transport) Close() error {
	if t.conn == nil {
		return nil
	}

	return t.conn.Close()
}
