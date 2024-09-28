<?php

use RrConcurrency\Events\JobHandledEvent;
use RrConcurrency\Events\JobHandlingErrorEvent;
use RrConcurrency\Events\JobReceivedEvent;
use RrConcurrency\Events\WorkerServeErrorEvent;
use RrConcurrency\Events\JobWaitingErrorEvent;
use RrConcurrency\Events\WorkerStartedEvent;
use RrConcurrency\Events\WorkerStoppedEvent;

return [
    'rpc'     => [
        'host' => env('RR_CONCURRENCY_RPC_HOST', '0.0.0.0'),
        'port' => env('RR_CONCURRENCY_RPC_PORT', '9010'),
    ],
    'workers' => [
        'number'     => env('RR_CONCURRENCY_WORKERS_NUMBER', 5),
        'max_number' => env('RR_CONCURRENCY_WORKERS_MAX_NUMBER', 10),
    ],
    'jobs'    => [
        'listeners' => [
            JobHandledEvent::class           => [],
            JobHandlingErrorEvent::class => [
                \RrConcurrency\Listeners\JobHandlingErrorListener::class,
            ],
            JobReceivedEvent::class      => [],
            WorkerServeErrorEvent::class => [
                \RrConcurrency\Listeners\WorkerServeErrorListener::class,
            ],
            JobWaitingErrorEvent::class  => [
                \RrConcurrency\Listeners\JobWaitingErrorListener::class,
            ],
            WorkerStartedEvent::class    => [],
            WorkerStoppedEvent::class    => [],
        ],
    ],
    'kv'      => [
        'storage-name' => env('RR_CONCURRENCY_KV_STORAGE_NAME', 'concurrency'),
    ],
];
