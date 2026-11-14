package receiverConfig

import (
	"os"
	"sync"
)

var once sync.Once
var instance Config

type Config interface {
	SocketPort() string
}

type receiver struct {
	socketPort string
}

func Get() Config {
	once.Do(func() {
		instance = &receiver{
			socketPort: os.Getenv("SOCKET_PORT"),
		}
	})

	return instance
}

func (c *receiver) SocketPort() string {
	return c.socketPort
}
