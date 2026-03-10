<?php

use RrMonitor\Events\MonitorWorkersCountSetEvent;

return [
    'rpc'       => [
        'host' => env('RR_MONITOR_RPC_HOST', env('OCTANE_RR_RPC_HOST')),
        'port' => env('RR_MONITOR_RPC_PORT', env('OCTANE_RR_RPC_PORT')),
    ],
    'listeners' => [
        MonitorWorkersCountSetEvent::class => [],
    ],
    'plugins'   => [
        'http' => [
            'number'     => env('OCTANE_RR_WORKERS', 5),
            'max_number' => env('OCTANE_RR_MAX_WORKERS', 10),
        ],
    ],
];
