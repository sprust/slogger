<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Telemetry panel stats endpoint
    |--------------------------------------------------------------------------
    | Full URL of the master telemetry panel stats endpoint as reachable from the
    | app (master runs in the workers container, the app in php-fpm). The dashboard
    | client GETs this URL with the adminToken bearer.
    */
    'panel_host' => env('SCONCUR_PANEL_HOST', 'http://127.0.0.1:28081/api/stats'),

    /*
    |--------------------------------------------------------------------------
    | Scoped services
    |--------------------------------------------------------------------------
    | Extra container aliases that must be resolved per-coroutine (in addition to
    | the built-in ScopedService set: request, session, auth, auth.driver, cookie).
    | See docs/fiber-safe-laravel-bridge.ru.md.
    */
    'scoped_services' => [
        // \Some\Package\Manager::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Master config
    |--------------------------------------------------------------------------
    | Full mirror of vendor/sconcur/sconcur/config/sconcur.servers.config.json.
    | Keys are kept verbatim (camelCase) so this array can be serialized straight
    | into the JSON master config consumed by bin/sconcur-server (MasterCli).
    | Values are env-driven with project defaults (cf. servers/sconcur/...).
    |
    | The top level is what belongs to the master as a whole; the pools it
    | supervises are the `groups` list (SConcur 0.11 moved workerScript,
    | workerCount, workerArgs and server there — one master now runs several
    | unlike pools under one lock and one journal).
    */
    'http_server' => [
        'phpBinary'           => env('SCONCUR_HTTP_PHP_BINARY', 'php'),
        'phpArgs'             => [],
        'panelPort'           => (int) env('SCONCUR_HTTP_PANEL_PORT', 28081),
        'adminToken'          => env('SCONCUR_HTTP_ADMIN_TOKEN', ''),
        'runtimeDir'          => storage_path('sconcur/runtime'),
        'logDir'              => storage_path('sconcur/logs'),
        'name'                => env('SCONCUR_HTTP_NAME', 'sconcur-http-server'),
        'rotateDays'          => (int) env('SCONCUR_HTTP_ROTATE_DAYS', 3),
        'logTo'               => env('SCONCUR_HTTP_LOG_TO', 'both'),
        'restartPolicy'       => env('SCONCUR_HTTP_RESTART_POLICY', 'always'),
        'shutdownTimeoutMs'   => (int) env('SCONCUR_HTTP_SHUTDOWN_TIMEOUT_MS', 10000),
        'restartBackoffMs'    => (int) env('SCONCUR_HTTP_RESTART_BACKOFF_MS', 200),
        'maxRestartBackoffMs' => (int) env('SCONCUR_HTTP_MAX_RESTART_BACKOFF_MS', 30000),

        // array_filter keeps this a list because the http group above it is
        // unconditional; a conditional group added before it would need array_values.
        'groups' => array_filter([
            [
                // The master spawns workers as: phpBinary phpArgs workerScript workerArgs --masterPid=N
                // i.e. `php artisan sconcur:servers:http:start --masterPid=N`.
                'name'         => 'http',
                'workerScript' => base_path('artisan'),
                'workerCount'  => (int) env('SCONCUR_HTTP_WORKER_COUNT', 1),
                'workerArgs'   => ['sconcur:servers:http:start'],
                // Forwarded to the worker's argv verbatim, which is where
                // HttpServer::fromArgs reads it back.
                'server'       => [
                    'address'             => env('SCONCUR_HTTP_ADDRESS', '0.0.0.0:28080'),
                    'reusePort'           => (bool) env('SCONCUR_HTTP_REUSE_PORT', true),
                    'maxRequests'         => (int) env('SCONCUR_HTTP_MAX_REQUESTS', 0),
                    'maxConcurrency'      => (int) env('SCONCUR_HTTP_MAX_CONCURRENCY', 0),
                    'maxRequestBody'      => (int) env('SCONCUR_HTTP_MAX_REQUEST_BODY', 10485760),
                    'readHeaderTimeoutMs' => (int) env('SCONCUR_HTTP_READ_HEADER_TIMEOUT_MS', 10000),
                    'readTimeoutMs'       => (int) env('SCONCUR_HTTP_READ_TIMEOUT_MS', 30000),
                    'writeTimeoutMs'      => (int) env('SCONCUR_HTTP_WRITE_TIMEOUT_MS', 30000),
                    'idleTimeoutMs'       => (int) env('SCONCUR_HTTP_IDLE_TIMEOUT_MS', 60000),
                    'handlerTimeoutMs'    => (int) env('SCONCUR_HTTP_HANDLER_TIMEOUT_MS', 60000),
                    'shutdownTimeoutMs'   => (int) env('SCONCUR_HTTP_SERVER_SHUTDOWN_TIMEOUT_MS', 5000),
                ],
            ],

            /*
            | The queue-consumer pool. A worker count below one leaves the group out of
            | the master config entirely, which is how the pool is turned off: setting
            | workerCount to 0 would not do it — to the master that means one worker per
            | CPU, not none.
            */
            (int) env('SCONCUR_RABBITMQ_WORKER_COUNT', 1) < 1 ? null : [
                'name'         => 'rabbitmq',
                'workerScript' => base_path('artisan'),
                'workerCount'  => (int) env('SCONCUR_RABBITMQ_WORKER_COUNT', 1),
                'workerArgs'   => ['sconcur:servers:rabbitmq:start'],
                'server'       => [
                    // Queues and their weights: how many consumers each gets, every
                    // one on its own channel. A handler still runs in its own coroutine
                    // per message. The master JSON-encodes it on the way to argv.
                    //
                    // The weights are what the supervisor used to carry as process
                    // counts: five `queue:work` for the default queue, one each for the
                    // other two.
                    'queues'           => [
                        [
                            'name'           => 'default',
                            'coroutineCount' => (int) env('SCONCUR_RABBITMQ_DEFAULT_CONSUMERS', 5),
                        ],
                        [
                            'name'           => env('QUEUE_TRACE_TREE_NAME', 'trace-tree'),
                            'coroutineCount' => (int) env('QUEUE_TRACE_TREE_WORKERS_COUNT', 1),
                        ],
                        [
                            'name'           => env('QUEUE_TRACES_CLEANER_NAME', 'traces-clearing'),
                            'coroutineCount' => (int) env('SCONCUR_RABBITMQ_CLEANER_CONSUMERS', 1),
                        ],
                    ],
                    // One is the right answer for a coroutine pool: the next message
                    // goes to a free coroutine rather than into a busy one's buffer.
                    'prefetchCount'    => (int) env('SCONCUR_RABBITMQ_PREFETCH_COUNT', 1),
                    // No deadline by default: trace-tree and traces-clearing ran under
                    // `queue:work --timeout=0` before this pool replaced them, and a tree
                    // build is legitimately long. A deadline here would refuse the job,
                    // not slow it down.
                    'handlerTimeoutMs' => (int) env('SCONCUR_RABBITMQ_HANDLER_TIMEOUT_MS', 0),
                    // False dead-letters a failed message (or drops it where the queue
                    // names no exchange); true loops forever on one that always fails.
                    // Retries are the job's own business, through release().
                    'requeueOnFailure'  => (bool) env('SCONCUR_RABBITMQ_REQUEUE_ON_FAILURE', false),
                    'maxMessages'       => (int) env('SCONCUR_RABBITMQ_MAX_MESSAGES', 0),
                    'maxRuntimeSeconds' => (int) env('SCONCUR_RABBITMQ_MAX_RUNTIME_SECONDS', 0),
                    'maxMemoryBytes'    => (int) env('SCONCUR_RABBITMQ_MAX_MEMORY_BYTES', 0),
                ],
            ],
        ]),

    ],

    /*
    |--------------------------------------------------------------------------
    | Queue transports
    |--------------------------------------------------------------------------
    | One section per transport, the same split as src/Queue/<Transport>.
    |
    | `connection` names the config/queue.php entry the consumer runs jobs on; it is
    | expected to use the `sconcur_rabbitmq` driver. `queues` is what
    | sconcur:rabbitmq:declare declares — it must list every queue the pool above
    | consumes, since the consumer runtime declares nothing itself.
    */
    'queue' => [
        'rabbitmq' => [
            'connection' => env('SCONCUR_RABBITMQ_CONNECTION', 'sconcur_rabbitmq'),

            // The same names the application publishes to, so the topology cannot
            // drift from where the jobs actually go.
            'queues' => [
                'default',
                env('QUEUE_TRACE_TREE_NAME', 'trace-tree'),
                env('QUEUE_TRACES_CLEANER_NAME', 'traces-clearing'),
            ],

            // Attempts before Worker::process() writes the job to failed_jobs, and the
            // delay it releases with. Both are the defaults; a job's own $tries and
            // $backoff win over them.
            'tries'     => (int) env('SCONCUR_RABBITMQ_TRIES', 1),
            'backoff'   => (int) env('SCONCUR_RABBITMQ_BACKOFF', 0),
            'memory_mb' => (int) env('SCONCUR_RABBITMQ_MEMORY_MB', 128),
        ],
    ],
];
