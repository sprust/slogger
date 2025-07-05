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
        'grpc' => [
            'number'     => env('OCTANE_RR_GRPC_WORKERS', 5),
            'max_number' => env('OCTANE_RR_GRPC_MAX_WORKERS', 10),
        ],
    ],
];
