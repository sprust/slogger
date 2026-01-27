<?php

declare(strict_types=1);

namespace SLoggerLaravel\Dispatcher\Items\Queue\ApiClients\Socket;

use Illuminate\Support\Carbon;
use SLoggerLaravel\Dispatcher\Items\Queue\ApiClients\ApiClientInterface;
use SLoggerLaravel\Objects\TracesObject;

class SocketClient implements ApiClientInterface
{
    public function __construct(
        protected string $apiToken,
        protected Connection $connection,
    ) {
    }

    public function sendTraces(TracesObject $traces): void
    {
        $this->connectIfNeed();

        $iterator = $traces->iterateCreating();

        $creatableTraces = [];

        foreach ($iterator as $trace) {
            $creatableTraces[] = [
                'tid' => $trace->traceId,
                ...(is_null($trace->parentTraceId) ? [] : ['ptid' => $trace->parentTraceId]),
                'tp'  => $trace->type,
                'st'  => $trace->status,
                ...(!count($trace->tags) ? [] : ['tgs' => $trace->tags]),
                'dt'  => $trace->data,
                ...(is_null($trace->duration) ? [] : ['dur' => $trace->duration]),
                ...(is_null($trace->memory) ? [] : ['mem' => $trace->memory]),
                ...(is_null($trace->cpu) ? [] : ['cpu' => $trace->cpu]),
                ...(!$trace->isParent ? [] : ['isp' => $trace->isParent]),
                'lat' => $this->prepareLoggedAt($trace->loggedAt),
            ];
        }

        $updatableTraces = [];

        $iterator = $traces->iterateUpdating();

        foreach ($iterator as $trace) {
            $updatableTraces[] = [
                'tid'  => $trace->traceId,
                'st'   => $trace->status,
                ...(is_null($trace->tags) ? [] : ['tgs' => $trace->tags]),
                ...(is_null($trace->data) ? [] : ['dt' => $trace->data]),
                ...(is_null($trace->duration) ? [] : ['dur' => $trace->duration]),
                ...(is_null($trace->memory) ? [] : ['mem' => $trace->memory]),
                ...(is_null($trace->cpu) ? [] : ['cpu' => $trace->cpu]),
                'plat' => $this->prepareLoggedAt($trace->parentLoggedAt),
            ];
        }

        $payload = [
            ...(count($creatableTraces) ? ['c' => json_encode($creatableTraces)] : []),
            ...(count($updatableTraces) ? ['u' => json_encode($updatableTraces)] : []),
        ];

        if (count($payload) === 0) {
            return;
        }

        $this->connection->write(
            json_encode($payload)
        );
    }

    protected function connectIfNeed(): void
    {
        if (!$this->connection->isConnected()) {
            $this->connection->connect(
                apiToken: $this->apiToken
            );
        }
    }

    protected function prepareLoggedAt(Carbon $loggedAt): float
    {
        return (float) ($loggedAt->unix() . '.' . $loggedAt->microsecond);
    }
}
