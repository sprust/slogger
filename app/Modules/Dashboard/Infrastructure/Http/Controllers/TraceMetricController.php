<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Http\Controllers;

use App\Modules\Dashboard\Domain\UseCases\FindTraceMetricsByAdminUseCase;
use App\Modules\Dashboard\Infrastructure\Http\Resources\TraceMetricResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

readonly class TraceMetricController
{
    public function __construct(
        private FindTraceMetricsByAdminUseCase $findTraceMetricsByAdminUseCase
    ) {
    }

    #[OaListItemTypeAttribute(TraceMetricResource::class)]
    public function index(int $serviceId): AnonymousResourceCollection
    {
        return TraceMetricResource::collection(
            $this->findTraceMetricsByAdminUseCase->handle(
                serviceId: $serviceId,
                now: Carbon::now(),
            )
        );
    }
}
