<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Trace\Domain\Actions\Mutations\DeleteTraceDynamicIndexAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDynamicIndexesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDynamicIndexStatsAction;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceDynamicIndexResource;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceDynamicIndexStatsResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

readonly class TraceDynamicIndexController
{
    public function __construct(
        private FindTraceDynamicIndexesAction $findTraceDynamicIndexesAction,
        private FindTraceDynamicIndexStatsAction $findTraceDynamicIndexStatsAction,
        private DeleteTraceDynamicIndexAction $deleteTraceDynamicIndexAction
    ) {
    }

    #[OaListItemTypeAttribute(TraceDynamicIndexResource::class)]
    public function index(): AnonymousResourceCollection
    {
        $traces = $this->findTraceDynamicIndexesAction->handle();

        return TraceDynamicIndexResource::collection($traces);
    }

    public function stats(): TraceDynamicIndexStatsResource
    {
        $stats = $this->findTraceDynamicIndexStatsAction->handle();

        return new TraceDynamicIndexStatsResource($stats);
    }

    public function destroy(string $id): void
    {
        $deleted = $this->deleteTraceDynamicIndexAction->handle(
            id: $id
        );

        abort_if(
            boolean: !$deleted,
            code: Response::HTTP_INTERNAL_SERVER_ERROR,
            message: 'Index not deleted'
        );
    }
}
