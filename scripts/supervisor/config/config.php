<?php

return [
    'processes' => [
        [
            'command'          => 'cron:start',
            'processes_number' => 1,
            'memory_limit'     => '128M',
        ],
    ],
];
