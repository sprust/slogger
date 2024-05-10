<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\ProfilingItemObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceProfilingResource extends AbstractApiResource
{
    private string $id;
    private string $call;
    private int $number_of_calls;
    private float $wait_time_in_us;
    private float $cpu_time;
    private float $memory_usage_in_bytes;
    private float $peak_memory_usage_in_bytes;
    #[OaListItemTypeAttribute(TraceProfilingResource::class, isRecursive: true)]
    private array $callables;

    public function __construct(ProfilingItemObject $resource)
    {
        parent::__construct($resource);

        $this->id                         = $resource->id;
        $this->call                       = $resource->call;
        $this->number_of_calls            = $resource->numberOfCalls;
        $this->wait_time_in_us            = $resource->waitTimeInUs;
        $this->cpu_time                   = $resource->cpuTime;
        $this->memory_usage_in_bytes      = $resource->memoryUsageInBytes;
        $this->peak_memory_usage_in_bytes = $resource->peakMemoryUsageInBytes;
        $this->callables                  = TraceProfilingResource::mapIntoMe($resource->callables);
    }
}
