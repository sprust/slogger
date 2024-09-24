<?php

use RrConcurrency\Events\PayloadHandledEvent;
use RrConcurrency\Events\PayloadHandlingErrorEvent;
use RrConcurrency\Events\PayloadReceivedEvent;
use RrConcurrency\Events\JobsServerErrorEvent;
use RrConcurrency\Events\WorkerErrorEvent;
use RrConcurrency\Events\WorkerStartingEvent;
use RrConcurrency\Events\WorkerStoppingEvent;

return [
    'rpc'  => [
        'host' => env('RR_CONCURRENCY_RPC_HOST', '0.0.0.0'),
        'port' => env('RR_CONCURRENCY_RPC_PORT', '9010'),
    ],
    'jobs' => [
        'listeners' => [
            PayloadHandledEvent::class       => [],
            PayloadHandlingErrorEvent::class => [],
            PayloadReceivedEvent::class      => [],
            JobsServerErrorEvent::class      => [],
            WorkerErrorEvent::class          => [],
            WorkerStartingEvent::class       => [],
            WorkerStoppingEvent::class       => [],
        ],
    ],
];
