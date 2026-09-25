<?php

return [
    'sources' => [
        [
            'folder'  => storage_path('logs'),
            'pattern' => '*.log',
            'type'    => 'laravel',
        ],
        [
            'folder'  => storage_path('logs/slogger'),
            'pattern' => '*.log',
            'type'    => 'laravel',
        ],
        [
            'folder'  => env('LOGS_NGINX_PATH', storage_path('logs/nginx')),
            'pattern' => 'access*.log',
            'type'    => 'nginx_access',
        ],
        [
            'folder'  => env('LOGS_NGINX_PATH', storage_path('logs/nginx')),
            'pattern' => 'error*.log',
            'type'    => 'nginx_error',
        ],
    ],

    'index' => [
        'path'         => env('LOGS_INDEX_PATH', storage_path('framework/logs-index')),
        'window_bytes' => (int) env('LOGS_INDEX_WINDOW_BYTES', 4 * 1024 * 1024),
    ],
];
