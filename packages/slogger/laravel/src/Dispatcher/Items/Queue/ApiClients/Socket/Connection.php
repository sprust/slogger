<?php

declare(strict_types=1);

namespace SLoggerLaravel\Dispatcher\Items\Queue\ApiClients\Socket;

use LogicException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SConcur\Exceptions\ResponseIsNotJsonException;
use SConcur\Exceptions\UnexpectedResponseFormatException;
use Throwable;

class Connection
{
    /**
     * @var resource|null
     */
    protected mixed $socket = null;

    protected int $socketBufferSize = 8024;

    protected bool $connected = false;

    protected string $socketAddress = '';

    protected int $lengthPrefixLength = 4;

    /**
     * @param string[] $socketAddresses
     */
    public function __construct(
        protected array $socketAddresses,
        protected LoggerInterface $logger,
    ) {
        if (count($socketAddresses) === 0) {
            throw new RuntimeException('No socket addresses provided');
        }
    }

    /**
     * @throws UnexpectedResponseFormatException
     * @throws ResponseIsNotJsonException
     */
    public function connect(string $apiToken): void
    {
        $payload = json_encode([
            't' => $apiToken,
        ]);

        foreach ($this->socketAddresses as $socketAddress) {
            $this->disconnect();

            $errorString = '';

            try {
                $socket = @stream_socket_client(
                    address: $socketAddress,
                    error_code: $errno,
                    error_message: $errorString,
                    timeout: 2.0,
                );
            } catch (Throwable $exception) {
                $this->logger->error(
                    sprintf(
                        '%s: %s%s',
                        $socketAddress,
                        ($errorString ? "socket error: $errorString, message: " : ''),
                        $exception->getMessage()
                    )
                );

                continue;
            }

            if (!$socket) {
                $this->logger->error(
                    sprintf(
                        '%s: %s',
                        $socketAddress,
                        ($errorString ? "socket error: $errorString" : 'unknown error')
                    )
                );

                continue;
            }

            socket_set_blocking($socket, false);

            $this->socket        = $socket;
            $this->connected     = true;
            $this->socketAddress = $socketAddress;

            $this->write(
                payload: $payload
            );

            try {
                $response = $this->read();
            } catch (Throwable) {
                $this->disconnect();

                continue;
            }

            if ($response !== 'ok') {
                $this->logger->error(
                    "failed to connect to [$socketAddress]: $response"
                );

                $this->disconnect();

                continue;
            }

            $this->logger->debug(
                "connected to [$socketAddress]"
            );

            return;
        }
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            fclose($this->socket);
        }

        $this->socket        = null;
        $this->connected     = false;
        $this->socketAddress = '';
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function write(string $payload): void
    {
        $this->checkConnection();

        $payloadLength = strlen($payload);
        $buffer        = pack('N', $payloadLength) . $payload;
        $bufferLength  = $payloadLength + $this->lengthPrefixLength;

        $sentBytes  = 0;
        $bufferSize = $this->socketBufferSize;

        while ($sentBytes < $bufferLength) {
            $chunk = substr($buffer, $sentBytes, $bufferSize);

            try {
                $bytes = fwrite(
                    stream: $this->socket,
                    data: $chunk,
                );
            } catch (Throwable $exception) {
                throw new RuntimeException(
                    message: $exception->getMessage(),
                    previous: $exception,
                );
            }

            if ($bytes === false) {
                // TODO: check timeout
                continue;
            }

            $sentBytes += $bytes;
        }
    }

    public function read(): string
    {
        $this->checkConnection();

        $socket = $this->socket;

        $lengthHeader = '';

        while (strlen($lengthHeader) < 4) {
            try {
                $chunk = fread(
                    stream: $socket,
                    length: 4 - strlen($lengthHeader)
                );
            } catch (Throwable $exception) {
                throw new RuntimeException(
                    message: $exception->getMessage(),
                );
            }

            if ($chunk === false || $chunk === '') {
                // TODO: check timeout
                continue;
            }

            $lengthHeader .= $chunk;
        }

        $response   = ""; // TODO: what!?
        $dataLength = unpack('N', $lengthHeader)[1];
        $bufferSize = $this->socketBufferSize;

        while (strlen($response) < $dataLength) {
            $chunk = fread(
                stream: $socket,
                length: min($bufferSize, $dataLength - strlen($response))
            );

            if ($chunk === false || $chunk === '') {
                // TODO: check timeout

                continue;
            }

            $response .= $chunk;
        }

        return $response;
    }

    protected function checkConnection(): void
    {
        if (!$this->connected) {
            throw new LogicException(
                'Socket is not connected. Please call connect() first.'
            );
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
