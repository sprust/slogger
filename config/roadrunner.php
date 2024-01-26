<?php

use RoadRunner\Servers\Http\Events\RrHttpPsrRequestHandlingErrorEvent;
use RoadRunner\Servers\Http\Events\RrHttpRequestHandledEvent;
use RoadRunner\Servers\Http\Events\RrHttpRequestHandlingErrorEvent;
use RoadRunner\Servers\Http\Events\RrHttpRequestReceivedEvent;
use RoadRunner\Servers\Http\Events\RrHttpRequestTerminatedEvent;
use RoadRunner\Servers\Http\Events\RrHttpServerErrorEvent;
use RoadRunner\Servers\Http\Events\RrHttpWorkerErrorEvent;
use RoadRunner\Servers\Http\Events\RrHttpWorkerStartingEvent;
use RoadRunner\Servers\Http\Events\RrHttpWorkerStoppingEvent;
use RoadRunner\Servers\Jobs\Events\RrJobsPayloadHandledEvent;
use RoadRunner\Servers\Jobs\Events\RrJobsPayloadHandlingErrorEvent;
use RoadRunner\Servers\Jobs\Events\RrJobsPayloadReceivedEvent;
use RoadRunner\Servers\Jobs\Events\RrJobsServerErrorEvent;
use RoadRunner\Servers\Jobs\Events\RrJobsWorkerErrorEvent;
use RoadRunner\Servers\Jobs\Events\RrJobsWorkerStartingEvent;
use RoadRunner\Servers\Jobs\Events\RrJobsWorkerStoppingEvent;
use App\Listeners\RrHttpRequestHandlingErrorListener;
use App\Listeners\RrJobsWorkerErrorListener;

return [
    'rpc'  => [
        'host' => env('RR_RPC_HOST', '0.0.0.0'),
        'port' => env('RR_RPC_PORT', '9010'),
    ],
    'http' => [
        'max_requests_count' => env('RR_HTTP_MAX_REQUESTS_COUNT', 250),
        'listen'             => [
            RrHttpServerErrorEvent::class             => [],
            RrHttpWorkerStartingEvent::class          => [],
            RrHttpWorkerStoppingEvent::class          => [],
            RrHttpWorkerErrorEvent::class             => [],
            RrHttpRequestReceivedEvent::class         => [],
            RrHttpRequestHandlingErrorEvent::class    => [
                RrHttpRequestHandlingErrorListener::class,
            ],
            RrHttpRequestTerminatedEvent::class       => [],
            RrHttpRequestHandledEvent::class          => [],
            RrHttpPsrRequestHandlingErrorEvent::class => [],
        ],
    ],
    'jobs' => [
        'listen' => [
            RrJobsPayloadHandledEvent::class       => [],
            RrJobsPayloadHandlingErrorEvent::class => [
                RrJobsWorkerErrorListener::class,
            ],
            RrJobsPayloadReceivedEvent::class      => [],
            RrJobsServerErrorEvent::class          => [],
            RrJobsWorkerErrorEvent::class          => [],
            RrJobsWorkerStartingEvent::class       => [],
            RrJobsWorkerStoppingEvent::class       => [],
        ],
    ],
];
