<?php

use App\Models\Users\User;
use App\Modules\Trace\Infrastructure\Jobs\TraceCreateJob;
use App\Modules\Trace\Infrastructure\Jobs\TraceUpdateJob;
use App\Services\SLogger\SLoggerEventWatcher;
use App\Services\SLogger\SLoggerRrParallelJobWatcher;
use SLoggerLaravel\Dispatcher\Queue\Jobs\SLoggerTraceCreateJob;
use SLoggerLaravel\Dispatcher\Queue\Jobs\SLoggerTraceUpdateJob;
use SLoggerLaravel\Events\SLoggerWatcherErrorEvent;
use SLoggerLaravel\Listeners\SLoggerWatcherErrorListener;
use SLoggerLaravel\Watchers\EntryPoints\SLoggerCommandWatcher;
use SLoggerLaravel\Watchers\EntryPoints\SLoggerJobWatcher;
use SLoggerLaravel\Watchers\EntryPoints\SLoggerRequestWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerCacheWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerDatabaseWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerDumpWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerGateWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerHttpClientWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerLogWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerMailWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerModelWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerNotificationWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerScheduleWatcher;

$defaultQueueConnection = env('QUEUE_TRACES_CREATING_CONNECTION');

return [
    'enabled' => env('SLOGGER_ENABLED', false),

    'token' => env('SLOGGER_TOKEN'),

    'dispatchers' => [
        'default' => env('SLOGGER_DISPATCHER', 'queue'),

        'queue' => [
            'connection' => env('SLOGGER_DISPATCHER_QUEUE_CONNECTION', $defaultQueueConnection),
            'name'       => env('SLOGGER_DISPATCHER_QUEUE_NAME', 'slogger'),

            'api_clients' => [
                'default' => env('SLOGGER_DISPATCHER_QUEUE_API_CLIENT', 'http'),

                'http' => [
                    'url' => env('SLOGGER_DISPATCHER_QUEUE_HTTP_CLIENT_URL'),
                ],

                'grpc' => [
                    'url' => env('SLOGGER_DISPATCHER_QUEUE_GRPC_CLIENT_URL'),
                ],
            ],
        ],

        'transporter' => [
            'queue' => [
                'connection' => env('SLOGGER_DISPATCHER_TRANSPORTER_QUEUE_CONNECTION', $defaultQueueConnection),
                'name'       => env('SLOGGER_DISPATCHER_TRANSPORTER_QUEUE_NAME', 'slogger-transporter'),
            ],
        ],
    ],

    'profiling' => [
        'enabled'    => env('SLOGGER_PROFILING_ENABLED', false),
        'namespaces' => [
            'App\*',
        ],
    ],

    'log_channel' => env('SLOGGER_LOG_CHANNEL', 'daily'),

    'listeners' => [
        SLoggerWatcherErrorEvent::class => [
            SLoggerWatcherErrorListener::class,
        ],
    ],

    'watchers_customizing' => [
        'requests' => [
            'header_parent_trace_id_key' => env(
                'SLOGGER_REQUESTS_HEADER_PARENT_TRACE_ID_KEY',
                'x-parent-trace-id'
            ),

            /** url_patterns */
            'excepted_paths'             => [
                '/traces-api*',
            ],

            'input' => [
                /** url_patterns */
                'full_hiding'        => [
                    'admin-api/auth/login',
                ],
                /** url_pattern => keys */
                'headers_masking'    => [
                    '*' => [
                        'authorization',
                        'cookie',
                        'x-xsrf-token',
                    ],
                ],
                /** url_pattern => key_patterns */
                'parameters_masking' => [
                    '*' => [
                        '*token*',
                        '*password*',
                    ],
                ],
            ],

            'output' => [
                /** url_patterns */
                'full_hiding'     => [
                    'admin-api/auth/*',
                    'admin-api/trace-aggregator/trace-metrics',
                ],

                /** url_pattern => keys */
                'headers_masking' => [
                    '*' => [
                        'set-cookie',
                    ],
                ],

                /** url_pattern => key_patterns */
                'fields_masking'  => [
                    '*' => [
                        '*token*',
                        '*password*',
                    ],
                ],
            ],
        ],

        'commands' => [
            'excepted' => [
                'queue:work',
                'queue:listen',
                'schedule:run',
                'cron:start',
                'octane:start',
                'octane:reload',
                'octane:stop',
                'octane:roadrunner:start',
                'octane:roadrunner:reload',
                'octane:roadrunner:stop',
                'trace-dynamic-indexes:monitor:start',
                'rr-parallel:monitor:start',
                'rr-parallel:monitor:stop',
                'rr-monitor:start',
            ],
        ],

        'jobs' => [
            'excepted' => [
                TraceCreateJob::class,
                TraceUpdateJob::class,
                SLoggerTraceCreateJob::class,
                SLoggerTraceUpdateJob::class,
            ],
        ],

        'models' => [
            /** model_class => field_patterns */
            'masks' => [
                '*'         => [
                    '*token*',
                    '*password*',
                ],
                User::class => [
                    '*name*',
                    '*email*',
                ],
            ],
        ],
    ],

    'watchers' => [
        [
            'class'   => SLoggerRequestWatcher::class,
            'enabled' => env('SLOGGER_LOG_REQUESTS_ENABLED', false),
        ],
        [
            'class'   => SLoggerCommandWatcher::class,
            'enabled' => env('SLOGGER_LOG_COMMANDS_ENABLED', false),
        ],
        [
            'class'   => SLoggerDatabaseWatcher::class,
            'enabled' => env('SLOGGER_LOG_DATABASE_ENABLED', false),
        ],
        [
            'class'   => SLoggerLogWatcher::class,
            'enabled' => env('SLOGGER_LOG_LOG_ENABLED', false),
        ],
        [
            'class'   => SLoggerScheduleWatcher::class,
            'enabled' => env('SLOGGER_LOG_LOG_ENABLED', false),
        ],
        [
            'class'   => SLoggerJobWatcher::class,
            'enabled' => env('SLOGGER_LOG_JOBS_ENABLED', false),
        ],
        [
            'class'   => SLoggerModelWatcher::class,
            'enabled' => env('SLOGGER_LOG_MODEL_ENABLED', false),
        ],
        [
            'class'   => SLoggerGateWatcher::class,
            'enabled' => env('SLOGGER_LOG_GATE_ENABLED', false),
        ],
        [
            'class'   => SLoggerEventWatcher::class,
            'enabled' => env('SLOGGER_LOG_EVENT_ENABLED', false),
        ],
        [
            'class'   => SLoggerMailWatcher::class,
            'enabled' => env('SLOGGER_LOG_MAIL_ENABLED', false),
        ],
        [
            'class'   => SLoggerNotificationWatcher::class,
            'enabled' => env('SLOGGER_LOG_NOTIFICATION_ENABLED', false),
        ],
        [
            'class'   => SLoggerCacheWatcher::class,
            'enabled' => env('SLOGGER_LOG_CACHE_ENABLED', false),
        ],
        [
            'class'   => SLoggerDumpWatcher::class,
            'enabled' => env('SLOGGER_LOG_DUMP_ENABLED', false),
        ],
        [
            'class'   => SLoggerHttpClientWatcher::class,
            'enabled' => env('SLOGGER_LOG_HTTP_ENABLED', false),
        ],
        [
            'class'   => SLoggerRrParallelJobWatcher::class,
            'enabled' => env('SLOGGER_RR_PARALLEL_ENABLED', false),
        ],
    ],
];
