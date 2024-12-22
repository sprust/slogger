<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters\Profilling;

readonly class TraceFindProfilingParameters
{
    /**
     * @param string[]|null $excludedCallers
     */
    public function __construct(
        public string $traceId,
        public ?string $caller = null,
        public ?array $excludedCallers = null
    ) {
    }
}
