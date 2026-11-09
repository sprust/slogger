<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Parameters;

use Illuminate\Support\Carbon;

readonly class TraceTreeNodeParameters
{
    /**
     * @param string[] $tags
     */
    public function __construct(public ?int $serviceId, public string $traceId, public ?string $parentTraceId, public string $type, public array $tags, public string $status, public ?float $duration, public ?float $memory, public ?float $cpu, public ?int $pid, public Carbon $loggedAt)
    {
    }
}
