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
    | HTTP server master config
    |--------------------------------------------------------------------------
    | Full mirror of vendor/sconcur/sconcur/config/sconcur.http-server.config.json.
    | Keys are kept verbatim (camelCase) so this array can be serialized straight
    | into the JSON master config consumed by bin/sconcur-server (MasterCli).
    | Values are env-driven with project defaults (cf. servers/sconcur/...).
    */
    'http_server' => [
        // The master spawns workers as: phpBinary phpArgs workerScript workerArgs --masterPid=N
        // i.e. `php artisan sconcur:servers:http:start --masterPid=N`.
        'workerScript'        => base_path('artisan'),
        'workerCount'         => (int) env('SCONCUR_HTTP_WORKER_COUNT', 1),
        'phpBinary'           => env('SCONCUR_HTTP_PHP_BINARY', 'php'),
        'phpArgs'             => [],
        'workerArgs'          => ['sconcur:servers:http:start'],
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
        'server'              => [
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
];
