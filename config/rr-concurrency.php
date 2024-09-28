<?php

use RrConcurrency\Events\JobHandledEvent;
use RrConcurrency\Events\JobHandlingErrorEvent;
use RrConcurrency\Events\JobReceivedEvent;
use RrConcurrency\Events\MonitorAddedWorkersEvent;
use RrConcurrency\Events\MonitorRemovedExcessWorkersEvent;
use RrConcurrency\Events\MonitorRemovedFreeWorkersEvent;
use RrConcurrency\Events\WorkerServeErrorEvent;
use RrConcurrency\Events\JobWaitingErrorEvent;
use RrConcurrency\Events\WorkerStartedEvent;
use RrConcurrency\Events\WorkerStoppedEvent;
use RrConcurrency\Listeners\WorkerServeErrorListener;
use RrConcurrency\Listeners\JobHandlingErrorListener;
use RrConcurrency\Listeners\JobWaitingErrorListener;

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
            // workers
            WorkerServeErrorEvent::class            => [
                WorkerServeErrorListener::class,
            ],
            WorkerStartedEvent::class               => [],
            WorkerStoppedEvent::class               => [],
            // jobs
            JobWaitingErrorEvent::class             => [
                JobWaitingErrorListener::class,
            ],
            JobReceivedEvent::class                 => [],
            JobHandledEvent::class                  => [],
            JobHandlingErrorEvent::class            => [
                JobHandlingErrorListener::class,
            ],
            // monitor
            MonitorAddedWorkersEvent::class         => [],
            MonitorRemovedFreeWorkersEvent::class   => [],
            MonitorRemovedExcessWorkersEvent::class => [],
        ],
    ],
    'kv'      => [
        'storage-name' => env('RR_CONCURRENCY_KV_STORAGE_NAME', 'concurrency'),
    ],
];
