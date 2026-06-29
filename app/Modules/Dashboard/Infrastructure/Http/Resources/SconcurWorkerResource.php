<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Entities\SconcurWorkerObject;

class SconcurWorkerResource extends AbstractApiResource
{
    private int $pid;
    private bool $hung;
    private float $uptime_seconds;
    private float $cpu_percent;
    private int $memory_rss_bytes;
    private int $goroutines;
    private int $requests_in_flight;
    private int $requests_completed;
    private float $requests_avg_ms;

    public function __construct(SconcurWorkerObject $worker)
    {
        parent::__construct($worker);

        $this->pid                = $worker->pid;
        $this->hung               = $worker->hung;
        $this->uptime_seconds     = $worker->uptimeSeconds;
        $this->cpu_percent        = $worker->cpuPercent;
        $this->memory_rss_bytes   = $worker->memoryRssBytes;
        $this->goroutines         = $worker->goroutines;
        $this->requests_in_flight = $worker->requestsInFlight;
        $this->requests_completed = $worker->requestsCompleted;
        $this->requests_avg_ms    = $worker->requestsAvgMs;
    }
}
