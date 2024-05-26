<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindStatusesParameters;
use App\Modules\TraceAggregator\Domain\Entities\Transports\TraceStringFieldTransport;
use App\Modules\TraceAggregator\Framework\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\TraceAggregator\Repositories\Dto\TraceStringFieldDto;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceContentRepositoryInterface;

readonly class FindStatusesAction
{
    use MakeDataFilterParameterTrait;

    public function __construct(
        private TraceContentRepositoryInterface $repository
    ) {
    }

    /**
     * @return string[]
     */
    public function handle(TraceFindStatusesParameters $parameters): array
    {
        return array_map(
            fn(TraceStringFieldDto $dto) => TraceStringFieldTransport::toObject($dto),
            $this->repository->findStatuses($parameters)
        );
    }
}
