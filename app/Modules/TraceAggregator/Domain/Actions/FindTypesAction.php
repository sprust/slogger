<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindTypesParameters;
use App\Modules\TraceAggregator\Framework\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceContentRepositoryInterface;

readonly class FindTypesAction
{
    use MakeDataFilterParameterTrait;

    public function __construct(
        private TraceContentRepositoryInterface $repository
    ) {
    }

    /**
     * @return string[]
     */
    public function handle(TraceFindTypesParameters $parameters): array
    {
        return $this->repository->findTypes($parameters);
    }
}
