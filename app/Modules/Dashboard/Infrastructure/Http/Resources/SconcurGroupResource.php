<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Entities\SconcurGroupObject;

class SconcurGroupResource extends AbstractApiResource
{
    private string $name;
    private int $workers_total;
    private int $workers_hung;
    private float $cpu_percent;
    private int $memory_rss_bytes;
    private int $goroutines;
    private ?SconcurWorkResource $work;

    public function __construct(SconcurGroupObject $group)
    {
        parent::__construct($group);

        $this->name             = $group->name;
        $this->workers_total    = $group->workersTotal;
        $this->workers_hung     = $group->workersHung;
        $this->cpu_percent      = $group->cpuPercent;
        $this->memory_rss_bytes = $group->memoryRssBytes;
        $this->goroutines       = $group->goroutines;
        $this->work             = SconcurWorkResource::makeIfNotNull($group->work);
    }
}
