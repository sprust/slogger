<?php

namespace App\Modules\TracesAggregator\Parents\Dto\Parameters;

readonly class TracesAggregatorParentsParameters
{
    /**
     * @param TracesAggregatorParentsSortParameters[] $sort
     */
    public function __construct(
        public int $page = 1,
        public ?int $perPage = null,
        public ?string $type = null,
        public array $sort = []
    ) {
    }
}
