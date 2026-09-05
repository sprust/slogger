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
    private int $runtime_tasks;
    private ?SconcurWorkResource $work;
    private float $master_cpu_percent;
    private int $master_memory_rss_bytes;
    #[OaListItemTypeAttribute(SconcurGroupResource::class)]
    private array $groups;
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
        $this->runtime_tasks           = $stat->runtimeTasks;
        $this->work                    = SconcurWorkResource::makeIfNotNull($stat->work);
        $this->master_cpu_percent      = $stat->masterCpuPercent;
        $this->master_memory_rss_bytes = $stat->masterMemoryRssBytes;
        $this->groups                  = SconcurGroupResource::mapIntoMe($stat->groups);
        $this->workers                 = SconcurWorkerResource::mapIntoMe($stat->workers);
    }
}
