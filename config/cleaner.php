<?php

return [
    'queue' => [
        'connection' => env('QUEUE_TRACES_CLEANER_CONNECTION', env('QUEUE_CONNECTION')),
        'name'       => env('QUEUE_TRACES_CLEANER_NAME', 'traces-clearing'),
    ],
];
