<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Trace\Contracts\Actions\MakeTraceTimestampPeriodsActionInterface;
use App\Modules\Trace\Infrastructure\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\Trace\Infrastructure\Http\Resources\Timestamp\TraceTimestampPeriodResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class TraceTimestampPeriodsController
{
    use MakeDataFilterParameterTrait;

    public function __construct(
        private MakeTraceTimestampPeriodsActionInterface $makeTraceTimestampPeriodsAction
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
