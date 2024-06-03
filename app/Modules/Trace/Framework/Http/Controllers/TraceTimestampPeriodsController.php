<?php

namespace App\Modules\Trace\Framework\Http\Controllers;

use App\Modules\Trace\Domain\Actions\MakeTraceTimestampPeriodsAction;
use App\Modules\Trace\Framework\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\Trace\Framework\Http\Resources\Timestamp\TraceTimestampPeriodResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class TraceTimestampPeriodsController
{
    use MakeDataFilterParameterTrait;

    public function __construct(
        private MakeTraceTimestampPeriodsAction $makeTraceTimestampPeriodsAction
    ) {
    }

    #[OaListItemTypeAttribute(TraceTimestampPeriodResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return TraceTimestampPeriodResource::collection(
            $this->makeTraceTimestampPeriodsAction->handle()
        );
    }
}
