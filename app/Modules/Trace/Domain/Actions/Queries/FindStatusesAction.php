<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Entities\Parameters\TraceFindStatusesParameters;
use App\Modules\Trace\Domain\Entities\Transports\TraceDataFilterTransport;
use App\Modules\Trace\Domain\Entities\Transports\TraceStringFieldTransport;
use App\Modules\Trace\Framework\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\Trace\Repositories\Dto\TraceStringFieldDto;
use App\Modules\Trace\Repositories\Interfaces\TraceContentRepositoryInterface;

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
            $this->repository->findStatuses(
                serviceIds: $parameters->serviceIds,
                text: $parameters->text,
                loggedAtFrom: $parameters->loggingPeriod?->from,
                loggedAtTo: $parameters->loggingPeriod?->to,
                types: $parameters->types,
                tags: $parameters->tags,
                data: TraceDataFilterTransport::toDtoIfNotNull($parameters->data),
                hasProfiling: $parameters->hasProfiling,
            )
        );
    }
}
