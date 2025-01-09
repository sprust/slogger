<?php

namespace SLoggerLaravel\Dispatcher\Queue\Jobs;

use SLoggerLaravel\Dispatcher\Queue\ApiClients\SLoggerApiClientInterface;
use SLoggerLaravel\Objects\SLoggerTraceObjects;

class SLoggerTraceCreateJob extends AbstractSLoggerTraceJob
{
    public function __construct(
        private readonly string $traceObjectsJson,
    ) {
        parent::__construct();
    }

    protected function onHandle(SLoggerApiClientInterface $apiClient): void
    {
        $apiClient->sendTraces(
            SLoggerTraceObjects::fromJson($this->traceObjectsJson)
        );
    }
}
