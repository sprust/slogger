<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Entities\SconcurWorkerObject;

class SconcurWorkerResource extends AbstractApiResource
{
    private int $pid;
    private string $group;
    private bool $hung;
    private float $uptime_seconds;
    private float $cpu_percent;
    private int $memory_rss_bytes;
    private int $runtime_tasks;
    private ?SconcurWorkResource $work;

    public function __construct(SconcurWorkerObject $worker)
    {
        parent::__construct($worker);

        $this->pid              = $worker->pid;
        $this->group            = $worker->group;
        $this->hung             = $worker->hung;
        $this->uptime_seconds   = $worker->uptimeSeconds;
        $this->cpu_percent      = $worker->cpuPercent;
        $this->memory_rss_bytes = $worker->memoryRssBytes;
        $this->runtime_tasks    = $worker->runtimeTasks;
        $this->work             = SconcurWorkResource::makeIfNotNull($worker->work);
    }
}
