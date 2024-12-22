<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters\Profilling;

readonly class TraceUpdateProfilingObject
{
    /**
     * @param TraceUpdateProfilingDataObject[] $data
     */
    public function __construct(
        public string $raw,
        public string $calling,
        public string $callable,
        public array $data
    ) {
    }
}
