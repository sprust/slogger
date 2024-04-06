<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindTagsParameters;
use App\Modules\TraceAggregator\Framework\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceContentRepositoryInterface;

readonly class FindTagsAction
{
    use MakeDataFilterParameterTrait;

    public function __construct(
        private TraceContentRepositoryInterface $repository
    ) {
    }

    /**
     * @return string[]
     */
    public function handle(TraceFindTagsParameters $parameters): array
    {
        return $this->repository->findTags($parameters);
    }
}
