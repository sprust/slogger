<?php

use App\Models\Users\User;
use App\Modules\Trace\Infrastructure\Jobs\TraceCreateJob;
use App\Modules\Trace\Infrastructure\Jobs\TraceUpdateJob;
use App\Services\SLogger\EventWatcher;
use App\Services\SLogger\RrParallelJobWatcher;
use SLoggerLaravel\Dispatcher\Queue\Jobs\TraceCreateJob as SLoggerTraceCreateJob;
use SLoggerLaravel\Dispatcher\Queue\Jobs\TraceUpdateJob as SLoggerTraceUpdateJob;
use SLoggerLaravel\Events\WatcherErrorEvent;
use SLoggerLaravel\Listeners\WatcherErrorListener;
use SLoggerLaravel\Watchers\EntryPoints\CommandWatcher;
use SLoggerLaravel\Watchers\EntryPoints\JobWatcher;
use SLoggerLaravel\Watchers\EntryPoints\RequestWatcher;
use SLoggerLaravel\Watchers\Services\CacheWatcher;
use SLoggerLaravel\Watchers\Services\DatabaseWatcher;
use SLoggerLaravel\Watchers\Services\DumpWatcher;
use SLoggerLaravel\Watchers\Services\GateWatcher;
use SLoggerLaravel\Watchers\Services\HttpClientWatcher;
use SLoggerLaravel\Watchers\Services\LogWatcher;
use SLoggerLaravel\Watchers\Services\MailWatcher;
use SLoggerLaravel\Watchers\Services\ModelWatcher;
use SLoggerLaravel\Watchers\Services\NotificationWatcher;
use SLoggerLaravel\Watchers\Services\ScheduleWatcher;

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
        WatcherErrorEvent::class => [
            WatcherErrorListener::class,
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
                SLoggerTraceCreateJob::class,
                SLoggerTraceUpdateJob::class,
                TraceCreateJob::class,
                TraceUpdateJob::class,
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
            'class'   => RequestWatcher::class,
            'enabled' => env('SLOGGER_LOG_REQUESTS_ENABLED', false),
        ],
        [
            'class'   => CommandWatcher::class,
            'enabled' => env('SLOGGER_LOG_COMMANDS_ENABLED', false),
        ],
        [
            'class'   => DatabaseWatcher::class,
            'enabled' => env('SLOGGER_LOG_DATABASE_ENABLED', false),
        ],
        [
            'class'   => LogWatcher::class,
            'enabled' => env('SLOGGER_LOG_LOG_ENABLED', false),
        ],
        [
            'class'   => ScheduleWatcher::class,
            'enabled' => env('SLOGGER_LOG_LOG_ENABLED', false),
        ],
        [
            'class'   => JobWatcher::class,
            'enabled' => env('SLOGGER_LOG_JOBS_ENABLED', false),
        ],
        [
            'class'   => ModelWatcher::class,
            'enabled' => env('SLOGGER_LOG_MODEL_ENABLED', false),
        ],
        [
            'class'   => GateWatcher::class,
            'enabled' => env('SLOGGER_LOG_GATE_ENABLED', false),
        ],
        [
            'class'   => EventWatcher::class,
            'enabled' => env('SLOGGER_LOG_EVENT_ENABLED', false),
        ],
        [
            'class'   => MailWatcher::class,
            'enabled' => env('SLOGGER_LOG_MAIL_ENABLED', false),
        ],
        [
            'class'   => NotificationWatcher::class,
            'enabled' => env('SLOGGER_LOG_NOTIFICATION_ENABLED', false),
        ],
        [
            'class'   => CacheWatcher::class,
            'enabled' => env('SLOGGER_LOG_CACHE_ENABLED', false),
        ],
        [
            'class'   => DumpWatcher::class,
            'enabled' => env('SLOGGER_LOG_DUMP_ENABLED', false),
        ],
        [
            'class'   => HttpClientWatcher::class,
            'enabled' => env('SLOGGER_LOG_HTTP_ENABLED', false),
        ],
        [
            'class'   => RrParallelJobWatcher::class,
            'enabled' => env('SLOGGER_RR_PARALLEL_ENABLED', false),
        ],
    ],
];
