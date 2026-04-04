<?php

return [
    'queue' => [
        'connection' => env('QUEUE_TRACE_TREE_CONNECTION', env('QUEUE_CONNECTION')),
        'name'       => env('QUEUE_TRACE_TREE_NAME', 'trace-tree'),
    ],
];
