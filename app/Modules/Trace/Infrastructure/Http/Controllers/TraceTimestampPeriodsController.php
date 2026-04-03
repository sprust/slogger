<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Trace\Domain\Actions\MakeTraceTimestampPeriodsAction;
use App\Modules\Trace\Infrastructure\Http\Resources\Timestamp\TraceTimestampPeriodResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class TraceTimestampPeriodsController
{
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
