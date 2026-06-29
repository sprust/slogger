<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Entities\SconcurStatObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class SconcurStatResource extends AbstractApiResource
{
    private bool $available;
    private string $name;
    private int $workers_total;
    private int $workers_hung;
    private float $cpu_percent;
    private int $memory_rss_bytes;
    private int $goroutines;
    private int $requests_completed;
    private float $requests_avg_ms;
    private int $requests_in_flight;
    private float $master_cpu_percent;
    private int $master_memory_rss_bytes;
    #[OaListItemTypeAttribute(SconcurWorkerResource::class)]
    private array $workers;

    public function __construct(SconcurStatObject $stat)
    {
        parent::__construct($stat);

        $this->available               = $stat->available;
        $this->name                    = $stat->name;
        $this->workers_total           = $stat->workersTotal;
        $this->workers_hung            = $stat->workersHung;
        $this->cpu_percent             = $stat->cpuPercent;
        $this->memory_rss_bytes        = $stat->memoryRssBytes;
        $this->goroutines              = $stat->goroutines;
        $this->requests_completed      = $stat->requestsCompleted;
        $this->requests_avg_ms         = $stat->requestsAvgMs;
        $this->requests_in_flight      = $stat->requestsInFlight;
        $this->master_cpu_percent      = $stat->masterCpuPercent;
        $this->master_memory_rss_bytes = $stat->masterMemoryRssBytes;
        $this->workers                 = SconcurWorkerResource::mapIntoMe($stat->workers);
    }
}
