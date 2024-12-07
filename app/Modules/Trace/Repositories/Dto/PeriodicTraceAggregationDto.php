<?php

namespace App\Modules\Trace\Repositories\Dto;

readonly class PeriodicTraceAggregationDto
{
    /**
     * @param string                      $collectionName
     * @param array<array<string, mixed>> $pipeline
     */
    public function __construct(
        public string $collectionName,
        public array $pipeline,
    ) {
    }
}
