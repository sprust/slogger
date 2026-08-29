<?php

declare(strict_types=1);

use App\Modules\Trace\Infrastructure\Tasks\BuildTraceDynamicIndexesTask;
use App\Services\Tasks\CronTask;

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
    | Database
    |--------------------------------------------------------------------------
    | The connection from config/database.php that database.default is pointed at
    | inside every coroutine process — the HTTP worker, the queue-consumer pool and
    | the task pool. Elsewhere (migrations, tinker, php-fpm) the configured default
    | is left alone.
    |
    | It is a connection name rather than a flag so an application can name its own;
    | null turns the swap off and leaves every model on whatever it already used.
    */
    'database' => [
        'default_connection' => env('SCONCUR_DB_CONNECTION', 'sconcur_mysql'),
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
    'master' => [
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
                        [
                            // The trace queue. slogger:dispatcher:start used to spawn
                            // queue:work processes for it and supervise them itself; the
                            // count it carried is the consumer weight here.
                            'name'           => env('SLOGGER_DISPATCHER_QUEUE_NAME', 'slogger'),
                            'coroutineCount' => (int) env('SLOGGER_DISPATCHER_QUEUE_WORKERS_COUNT', 5),
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
            /*
            | The periodic task pool. Exactly one worker, always: a second one would tick
            | the cron twice a minute, and workerCount 0 does not mean none — to the
            | master it means one worker per CPU.
            |
            | No `server` block: the pool reads nothing from argv but the master's pid,
            | which the master appends by itself.
            |
            | Unlike the pools above, it reports to the panel from PHP rather than from a
            | runtime's Go side, because it runs no such runtime — see TaskPoolTelemetry
            | and the `tasks` section further down.
            */
            [
                'name'         => 'tasks',
                'workerScript' => base_path('artisan'),
                'workerCount'  => 1,
                'workerArgs'   => ['sconcur:tasks:start'],
                // Not the master's `always`: this pool is meant to be stoppable.
                // `sconcur:tasks:stop` drains the tasks and exits 0, and under `always`
                // the master would put a fresh pool up within the second — a stop that
                // does not stop. The one exit that does want a new process, the memory
                // limit, is non-zero on purpose (TaskPool::EXIT_RESTART).
                'restartPolicy' => 'on-failure',
                // Must exceed the pool's own shutdown deadline (20 s), or the master
                // kills it before the graceful stop can finish; and the supervisor's
                // stopwaitsecs for the master must in turn exceed this.
                'shutdownTimeoutMs' => (int) env('SCONCUR_TASKS_SHUTDOWN_TIMEOUT_MS', 30000),
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
                // SendTracesJob releases itself on failure, so this one needs its wait
                // queues as much as the others do.
                env('SLOGGER_DISPATCHER_QUEUE_NAME', 'slogger'),
            ],

            // Attempts before Worker::process() writes the job to failed_jobs, and the
            // delay it releases with. Both are the defaults; a job's own $tries and
            // $backoff win over them.
            'tries'     => (int) env('SCONCUR_RABBITMQ_TRIES', 1),
            'backoff'   => (int) env('SCONCUR_RABBITMQ_BACKOFF', 0),
            'memory_mb' => (int) env('SCONCUR_RABBITMQ_MEMORY_MB', 128),
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Periodic task pool
    |--------------------------------------------------------------------------
    | The third runtime of this package, beside the HTTP server and the queue-consumer
    | pool: one process running every task below as its own coroutine of a WaitGroup.
    |
    | A task implements tick() and nothing else — the loop, the pauses, the reporting and
    | the stop belong to the pool. `sconcur:tasks:stop` and `sconcur:tasks:restart` reach
    | a running pool through `control_key`, which is what lets another container manage
    | it. See the package's docs/task-pool.ru.md.
    */
    'tasks' => [
        'control_key' => env('SCONCUR_TASKS_CONTROL_KEY', 'sconcur:tasks:control'),

        // flock, not a cache lock: the kernel releases it when the process dies, SIGKILL
        // included, so a second pool cannot start beside the first and there is no stale
        // lock to clean up.
        'lock_path'   => env('SCONCUR_TASKS_LOCK_PATH', storage_path('sconcur/runtime/tasks.lock')),

        // A leak anywhere in the process takes every task down with it, so the limit is
        // the pool's. Passing it is a graceful stop, and the supervisor starts a fresh one.
        'memory_mb'   => (int) env('SCONCUR_TASKS_MEMORY_MB', 256),

        // How finely a pause is cut, which is how fast the pool notices a signal: a
        // pcntl handler only runs while PHP does, and a process whose coroutines are all
        // parked in Go executes none. The library's own servers poll on the same 250 ms.
        'sleep_chunk_ms' => (int) env('SCONCUR_TASKS_SLEEP_CHUNK_MS', 250),

        // Automatic coroutine switching, so a tick busy with pure computation cannot
        // starve the controller that carries the shutdown. Off by default, and that is a
        // measurement rather than caution — docs/task-pool.ru.md records it: on a pool of
        // two tasks where one is continuously in Mongo, a preempted coroutine did not get
        // the thread back for as long as the pool ran, and a five-second pause took
        // seventy-five. That is exactly the shape of this pool (cron beside the index
        // builder), so the measured value is the one that stands here.
        //
        // Turn it on for a task with a long computational stretch, and check on your own
        // set that its neighbours do not starve. A native blocking call is not preempted
        // either way; the shutdown deadline is what covers that.
        'preemption_quantum_ms' => (int) env('SCONCUR_TASKS_PREEMPTION_QUANTUM_MS', 0),

        // The tick counters that fill the panel's "In-flight / Handled / Refused"
        // columns for this pool, sent as the snapshot's `consumers` section — a tick is
        // to a task what a delivery is to a consumer. The catch is one level up: the
        // master sums that section across every worker, so its master-wide deliveries
        // per second and average duration will count ticks alongside the AMQP pool's
        // real deliveries. Per-group numbers stay clean either way; turn this off to
        // keep the pool out of the master's totals.
        'report_ticks' => (bool) env('SCONCUR_TASKS_REPORT_TICKS', true),

        // How long a stop waits for the running ticks before the group is unwound. Must
        // stay below the supervisor's stopwaitsecs for the pool's program (30 s), or the
        // process is always killed before this can happen and the graceful path never
        // runs at all.
        'shutdown_timeout_seconds' => (int) env('SCONCUR_TASKS_SHUTDOWN_TIMEOUT_SECONDS', 20),

        /*
        | The tasks themselves: a name to address them by, the class, and how long to
        | wait after each of the three tick outcomes — `idle` (no work), `busy` (work
        | done) and `backoff` (the tick threw).
        */
        'list' => [
            [
                'name'    => CronTask::NAME,
                'task'    => CronTask::class,
                'idle'    => 5,
                // Nothing to drain: the schedule fires on the minute either way.
                'busy'    => 5,
                'backoff' => 5,
            ],
            [
                'name'    => BuildTraceDynamicIndexesTask::NAME,
                'task'    => BuildTraceDynamicIndexesTask::class,
                'idle'    => 1,
                // There was work, so take the next batch straight away.
                'busy'    => 0,
                'backoff' => 3,
            ],
        ],
    ],
];
