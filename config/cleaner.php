<?php

return [
    'queue' => [
        'connection' => env('QUEUE_TRACES_CLEANER_CONNECTION', env('QUEUE_CONNECTION')),
        'name'       => env('QUEUE_TRACES_CLEANER_NAME', 'traces-clearing'),
    ],

    // traces older than this are dropped by the hour (partitions of the traces table)
    'lifetime_hours' => (int) env('TRACES_LIFETIME_HOURS', 72),
];
