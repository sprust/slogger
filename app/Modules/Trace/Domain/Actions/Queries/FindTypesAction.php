<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Entities\Parameters\TraceFindTypesParameters;
use App\Modules\Trace\Domain\Entities\Transports\TraceDataFilterTransport;
use App\Modules\Trace\Domain\Entities\Transports\TraceStringFieldTransport;
use App\Modules\Trace\Framework\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\Trace\Repositories\Dto\TraceStringFieldDto;
use App\Modules\Trace\Repositories\Interfaces\TraceContentRepositoryInterface;

readonly class FindTypesAction
{
    use MakeDataFilterParameterTrait;

    public function __construct(
        private TraceContentRepositoryInterface $traceContentRepository
    ) {
    }

    /**
     * @return string[]
     */
    public function handle(TraceFindTypesParameters $parameters): array
    {
        return array_map(
            fn(TraceStringFieldDto $dto) => TraceStringFieldTransport::toObject($dto),
            $this->traceContentRepository->findTypes(
                serviceIds: $parameters->serviceIds,
                text: $parameters->text,
                loggedAtFrom: $parameters->loggingPeriod?->from,
                loggedAtTo: $parameters->loggingPeriod?->to,
                data: TraceDataFilterTransport::toDtoIfNotNull($parameters->data),
                hasProfiling: $parameters->hasProfiling,
            )
        );
    }
}
