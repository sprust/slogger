<?php

namespace App\Modules\Trace\Framework\Http\Controllers;

use App\Modules\Trace\Domain\Actions\Queries\FindTraceTimestampsAction;
use App\Modules\Trace\Domain\Entities\Parameters\FindTraceTimestampsParameters;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Enums\TraceTimestampPeriodEnum;
use App\Modules\Trace\Framework\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\Trace\Framework\Http\Requests\TraceTimestampsRequest;
use App\Modules\Trace\Framework\Http\Resources\Timestamp\TraceTimestampsResource;
use Illuminate\Support\Carbon;

readonly class TraceTimestampsController
{
    use MakeDataFilterParameterTrait;

    public function __construct(
        private FindTraceTimestampsAction $findTraceTimestampsAction
    ) {
    }

    public function index(TraceTimestampsRequest $request): TraceTimestampsResource
    {
        $validated = $request->validated();

        return new TraceTimestampsResource(
            $this->findTraceTimestampsAction->handle(
                new FindTraceTimestampsParameters(
                    timestampPeriod: TraceTimestampPeriodEnum::from($validated['timestamp_period']),
                    timestampStep: TraceTimestampEnum::from($validated['timestamp_step']),
                    serviceIds: !is_null($validated['service_ids'] ?? null)
                        ? array_map('intval', $validated['service_ids'])
                        : null,
                    loggedAtTo: ($validated['logging_to'] ?? null)
                        ? new Carbon($validated['logging_to'])
                        : null,
                    types: $validated['types'] ?? [],
                    tags: $validated['tags'] ?? [],
                    statuses: $validated['statuses'] ?? [],
                    durationFrom: $validated['duration_from'] ?? null,
                    durationTo: $validated['duration_to'] ?? null,
                    data: $this->makeDataFilterParameter($validated),
                    hasProfiling: ($validated['has_profiling'] ?? null) ?: null,
                )
            )
        );
    }
}
