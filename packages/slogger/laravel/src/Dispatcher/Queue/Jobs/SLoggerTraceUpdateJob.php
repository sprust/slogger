<?php

namespace SLoggerLaravel\Dispatcher\Queue\Jobs;

use SLoggerLaravel\Dispatcher\Queue\ApiClients\SLoggerApiClientInterface;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObjects;

class SLoggerTraceUpdateJob extends AbstractSLoggerTraceJob
{
    public function __construct(
        private readonly string $traceObjectsJson,
    ) {
        parent::__construct();
    }

    protected function onHandle(SLoggerApiClientInterface $apiClient): void
    {
        $apiClient->updateTraces(
            SLoggerTraceUpdateObjects::fromJson($this->traceObjectsJson)
        );
    }
}
