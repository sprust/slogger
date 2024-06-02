<?php

namespace App\Modules\Trace\Framework\Http\Controllers;

use App\Modules\Trace\Domain\Actions\GetTraceTimestampPeriodsAction;
use App\Modules\Trace\Framework\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\Trace\Framework\Http\Resources\TraceTimestampPeriodResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class TraceTimestampPeriodsController
{
    use MakeDataFilterParameterTrait;

    public function __construct(
        private GetTraceTimestampPeriodsAction $getTraceTimestampPeriodsAction
    ) {
    }

    #[OaListItemTypeAttribute(TraceTimestampPeriodResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return TraceTimestampPeriodResource::collection(
            $this->getTraceTimestampPeriodsAction->handle()
        );
    }
}