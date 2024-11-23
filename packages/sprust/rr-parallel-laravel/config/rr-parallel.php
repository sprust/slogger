<?php

use Laravel\Octane\Octane;
use RrParallel\Events\JobHandledEvent;
use RrParallel\Events\JobHandlingErrorEvent;
use RrParallel\Events\JobReceivedEvent;
use RrParallel\Events\JobWaitingErrorEvent;
use RrParallel\Events\WorkerServeErrorEvent;
use RrParallel\Events\WorkerStartingEvent;
use RrParallel\Events\WorkerStoppedEvent;
use RrParallel\Listeners\JobHandlingErrorListener;
use RrParallel\Listeners\JobWaitingErrorListener;
use RrParallel\Listeners\WorkerServeErrorListener;

return [
    'driver'  => env('RR_PARALLEL_DRIVER', 'rr'),
    'rpc'     => [
        'host' => env('RR_PARALLEL_RPC_HOST', '0.0.0.0'),
        'port' => env('RR_PARALLEL_RPC_PORT', '9010'),
    ],
    'workers' => [
        'number'       => env('RR_PARALLEL_WORKERS_NUMBER', 5),
        'max_number'   => env('RR_PARALLEL_WORKERS_MAX_NUMBER', 10),
        'memory_limit' => env('RR_PARALLEL_WORKERS_MAX_MEMORY_LIMIT', 128),
        'max_jobs'     => env('RR_PARALLEL_WORKERS_MAX_JOBS', 1000),
    ],
    'jobs'    => [
        'listeners' => [
            // workers
            WorkerServeErrorEvent::class => [
                WorkerServeErrorListener::class,
            ],
            WorkerStartingEvent::class   => [],
            WorkerStoppedEvent::class    => [],
            // jobs
            JobWaitingErrorEvent::class  => [
                JobWaitingErrorListener::class,
            ],
            JobReceivedEvent::class      => [
                ...Octane::prepareApplicationForNextOperation(),
            ],
            JobHandledEvent::class       => [],
            JobHandlingErrorEvent::class => [
                JobHandlingErrorListener::class,
            ],
        ],
    ],
    'kv'      => [
        'storage-name' => env('RR_PARALLEL_KV_STORAGE_NAME', 'parallel'),
    ],
];
