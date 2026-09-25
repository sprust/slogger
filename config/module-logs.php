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
    // keep_days — optional: logs:clean, once a day, deletes the source's files not written
    //           to for longer than this. Leave it out where something else rotates the
    //           files — Laravel's daily channel keeps its own `days`.
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
            'pattern'   => 'access*.log',
            'type'      => LogTypeEnum::NginxAccess,
            'keep_days' => (int) env('LOGS_NGINX_KEEP_DAYS', 14),
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

    // One request to the entries endpoint, over however many files it names.
    'search' => [
        // Most files one request may name.
        'max_files'       => (int) env('LOGS_SEARCH_MAX_FILES', 200),
        // How many files are indexed, and read, at the same time.
        'concurrency'     => (int) env('LOGS_SEARCH_CONCURRENCY', 4),
        // How long a request may spend bringing indexes up to date before it answers
        // with indexing: true, and the page asks again.
        'index_budget_ms' => (int) env('LOGS_SEARCH_INDEX_BUDGET_MS', 5000),
        // How long, and how many bytes of entries, one request may look through before it
        // answers with what it has found and a cursor to go on from.
        'time_budget_ms'  => (int) env('LOGS_SEARCH_TIME_BUDGET_MS', 2000),
        'bytes_budget'    => (int) env('LOGS_SEARCH_BYTES_BUDGET', 64 * 1024 * 1024),
        // How many entries of one file a search reads between two looks at the budget.
        'block_records'   => (int) env('LOGS_SEARCH_BLOCK_RECORDS', 1000),
        // Entries on one page: the default and the most a request may ask for.
        'per_page'        => (int) env('LOGS_SEARCH_PER_PAGE', 50),
        'max_per_page'    => 500,
    ],

    // The logErrors watcher counts ERROR and above in the Laravel sources through the same
    // indexes. When another process is extending an index, the count waits this long and
    // then reads the index as it is, a little behind the file.
    'errors' => [
        'wait_for_lock_sec' => (int) env('LOGS_ERRORS_WAIT_FOR_LOCK_SEC', 5),
    ],

    'reading' => [
        // The most of one entry the page receives and a search looks through; a longer
        // entry comes cut and marked as truncated.
        'max_entry_bytes' => (int) env('LOGS_MAX_ENTRY_BYTES', 1024 * 1024),
    ],

    'download' => [
        // The page saves a download through the browser's memory, so a bigger file is refused.
        'max_bytes' => (int) env('LOGS_DOWNLOAD_MAX_BYTES', 100 * 1024 * 1024),
    ],
];
