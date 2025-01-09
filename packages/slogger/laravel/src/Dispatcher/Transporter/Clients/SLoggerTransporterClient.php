<?php

namespace SLoggerLaravel\Dispatcher\Transporter\Clients;

use Illuminate\Contracts\Queue\Queue;

readonly class SLoggerTransporterClient implements SLoggerTransporterClientInterface
{
    public function __construct(
        private string $apiToken,
        private Queue $connection,
        private string $queueName,
    ) {
    }

    public function dispatch(array $actions): void
    {
        $this->connection->pushRaw(
            payload: json_encode([
                'id'      => uniqid(),
                'payload' => json_encode([
                    'tok' => $this->apiToken,
                    'acs' => $actions,
                ]),
            ]),
            queue: $this->queueName
        );
    }
}
