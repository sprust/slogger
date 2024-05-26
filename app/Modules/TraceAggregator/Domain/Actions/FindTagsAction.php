<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceStringFieldObject;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindTagsParameters;
use App\Modules\TraceAggregator\Domain\Entities\Transports\TraceStringFieldTransport;
use App\Modules\TraceAggregator\Framework\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\TraceAggregator\Repositories\Dto\TraceStringFieldDto;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceContentRepositoryInterface;

readonly class FindTagsAction
{
    use MakeDataFilterParameterTrait;

    public function __construct(
        private TraceContentRepositoryInterface $repository
    ) {
    }

    /**
     * @return TraceStringFieldObject[]
     */
    public function handle(TraceFindTagsParameters $parameters): array
    {
        return array_map(
            fn(TraceStringFieldDto $dto) => TraceStringFieldTransport::toObject($dto),
            $this->repository->findTags($parameters)
        );
    }
}
