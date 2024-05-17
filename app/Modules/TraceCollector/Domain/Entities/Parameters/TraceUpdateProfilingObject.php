<?php

namespace App\Modules\TraceCollector\Domain\Entities\Parameters;

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
