<?php

return [
    'queue' => [
        'connection' => env('QUEUE_TRACE_TREE_CONNECTION', env('QUEUE_CONNECTION')),
        'name'       => env('QUEUE_TRACE_TREE_NAME', 'trace-tree'),
    ],

    // Above this many nodes a tree is not sent whole: the panel opens it branch by
    // branch instead. The ceiling is the browser's — one JSON.parse and one object per
    // node — so it is tuned here rather than guessed in code.
    'tree' => [
        'full_load_limit' => (int) env('TRACE_TREE_FULL_LOAD_LIMIT', 200000),
    ],
];
