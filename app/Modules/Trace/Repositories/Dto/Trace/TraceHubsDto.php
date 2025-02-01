<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Trace;

readonly class TraceHubsDto
{
    /**
     * @param TraceHubDto[]        $traces
     * @param TraceHubInvalidDto[] $invalidTraces
     */
    public function __construct(
        public array $traces,
        public array $invalidTraces,
    ) {
    }
}
