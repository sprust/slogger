<?php

use RrMonitor\Events\MonitorWorkersCountSetEvent;

return [
    'rpc'       => [
        'host' => env('RR_MONITOR_RPC_HOST'),
        'port' => env('RR_MONITOR_RPC_PORT'),
    ],
    'listeners' => [
        // eventClass => listenerClass[]
        MonitorWorkersCountSetEvent::class => [],
    ],
    'plugins'   => [
        // example
        //'jobs' => [
        //    'number'     => env('RR_JOBS_WORKERS_NUMBER', 5),
        //    'max_number' => env('RR_JOBS_WORKERS_MAX_NUMBER', 5),
        //],
    ],
];
