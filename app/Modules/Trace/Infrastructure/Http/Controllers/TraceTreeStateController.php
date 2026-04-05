<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Domain\Actions\Mutations\CancelTraceTreeCacheStateAction;
use App\Modules\Trace\Domain\Actions\Mutations\DeleteTraceTreeCacheStateAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeCacheStatesAction;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceTreeRootTraceStateRequest;
use App\Modules\Trace\Infrastructure\Http\Resources\Tree\TraceTreeStateResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class TraceTreeStateController
{
    public function __construct(
        private FindTraceTreeCacheStatesAction $findTraceTreeCacheStatesAction,
        private CancelTraceTreeCacheStateAction $cancelTraceTreeCacheStateAction,
        private DeleteTraceTreeCacheStateAction $deleteTraceTreeCacheStateAction,
    ) {
    }

    #[OaListItemTypeAttribute(TraceTreeStateResource::class)]
    public function processes(): AnonymousResourceCollection
    {
        return TraceTreeStateResource::collection(
            $this->findTraceTreeCacheStatesAction->handle(
                limit: 50,
                excludeStatus: TraceTreeCacheStateStatusEnum::Finished
            )
        );
    }

    public function cancelProcess(TraceTreeRootTraceStateRequest $request): TraceTreeStateResource
    {
        $validated = $request->validated();

        $state = $this->cancelTraceTreeCacheStateAction->handle(
            ArrayValueGetter::string($validated, 'root_trace_id')
        );

        if ($state === null) {
            abort(404, 'State not found');
        }

        return new TraceTreeStateResource($state);
    }

    public function deleteProcess(TraceTreeRootTraceStateRequest $request): void
    {
        $validated = $request->validated();

        $this->deleteTraceTreeCacheStateAction->handle(
            rootTraceId: ArrayValueGetter::string($validated, 'root_trace_id'),
        );
    }
}
