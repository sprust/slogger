<?php

use App\Modules\Logs\Enums\LogTypeEnum;

return [
    // Where the Logs page looks for files. Each source is one folder read without
    // recursion (a subfolder is a source of its own), so sources must not overlap.
    //
    // name    — the group the page shows the source's files under.
    // folder  — an absolute path; a folder that does not exist is simply empty.
    // pattern — matched against the file name only: `*`, `?` and `[...]`, no `**`.
    // type    — how the files are parsed, one of LogTypeEnum:
    //           LogTypeEnum::Laravel     — Monolog LineFormatter: `[Y-m-d H:i:s] env.LEVEL: message {context} [extra]`,
    //                                      an entry runs until the next header (stack traces included),
    //                                      levels DEBUG…EMERGENCY;
    //           LogTypeEnum::NginxAccess — nginx `combined` format, one line per entry,
    //                                      the status class (1xx…5xx) stands for the level;
    //           LogTypeEnum::NginxError  — nginx error_log, one line per entry,
    //                                      levels debug…emerg.
    //           A line a format cannot read is still an entry, without a level.
    'sources' => [
        [
            'name'    => 'Laravel',
            'folder'  => storage_path('logs'),
            'pattern' => '*.log',
            'type'    => LogTypeEnum::Laravel,
        ],
        [
            'name'    => 'Slogger',
            'folder'  => storage_path('logs/slogger'),
            'pattern' => '*.log',
            'type'    => LogTypeEnum::Laravel,
        ],
        // nginx writes here through the volume in docker-compose.yml: one access file a
        // day (access-Y-m-d.log) and a single error.log. On a host with its own nginx,
        // point LOGS_NGINX_PATH at that nginx's log folder.
        [
            'name'    => 'Nginx',
            'folder'  => env('LOGS_NGINX_PATH', storage_path('logs/nginx')),
            'pattern' => 'access*.log',
            'type'    => LogTypeEnum::NginxAccess,
        ],
        [
            'name'    => 'Nginx',
            'folder'  => env('LOGS_NGINX_PATH', storage_path('logs/nginx')),
            'pattern' => 'error*.log',
            'type'    => LogTypeEnum::NginxError,
        ],
    ],

    'index' => [
        // One folder per log file: meta.json, entries.idx and a level-<n>.idx per level.
        // Safe to delete: an index is built again on the next read.
        'path'         => env('LOGS_INDEX_PATH', storage_path('framework/logs-index')),
        // How much of a log file one read takes while indexing. An entry longer than this
        // doubles the window up to 64 MiB; beyond that the entry is cut.
        'window_bytes' => (int) env('LOGS_INDEX_WINDOW_BYTES', 4 * 1024 * 1024),
    ],

    'reading' => [
        // The most of one entry the page receives and a search looks through; a longer
        // entry comes cut and marked as truncated.
        'max_entry_bytes' => (int) env('LOGS_MAX_ENTRY_BYTES', 1024 * 1024),
    ],
];
