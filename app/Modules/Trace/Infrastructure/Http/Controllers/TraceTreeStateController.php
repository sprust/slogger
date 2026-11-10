<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Domain\UseCases\CancelTraceTreeCacheStateByAdminUseCase;
use App\Modules\Trace\Domain\UseCases\DeleteTraceTreeCacheStateByAdminUseCase;
use App\Modules\Trace\Domain\UseCases\FindTraceTreeCacheStatesByAdminUseCase;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceTreeRootTraceStateRequest;
use App\Modules\Trace\Infrastructure\Http\Resources\Tree\TraceTreeStateResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class TraceTreeStateController
{
    public function __construct(
        private FindTraceTreeCacheStatesByAdminUseCase $findTraceTreeCacheStatesByAdminUseCase,
        private CancelTraceTreeCacheStateByAdminUseCase $cancelTraceTreeCacheStateByAdminUseCase,
        private DeleteTraceTreeCacheStateByAdminUseCase $deleteTraceTreeCacheStateByAdminUseCase
    ) {
    }

    #[OaListItemTypeAttribute(TraceTreeStateResource::class)]
    public function processes(): AnonymousResourceCollection
    {
        return TraceTreeStateResource::collection(
            $this->findTraceTreeCacheStatesByAdminUseCase->handle(
                limit: 50,
                excludeStatus: TraceTreeCacheStateStatusEnum::Finished,
            )
        );
    }

    public function cancelProcess(TraceTreeRootTraceStateRequest $request): TraceTreeStateResource
    {
        $validated = $request->validated();

        $state = $this->cancelTraceTreeCacheStateByAdminUseCase->handle(
            ArrayValueGetter::string(
                data: $validated,
                key: 'root_trace_id',
            )
        );

        return new TraceTreeStateResource($state);
    }

    public function deleteProcess(TraceTreeRootTraceStateRequest $request): void
    {
        $validated = $request->validated();

        $this->deleteTraceTreeCacheStateByAdminUseCase->handle(
            ArrayValueGetter::string(
                data: $validated,
                key: 'root_trace_id',
            ),
        );
    }
}
