<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Domain\Actions\Mutations\CreateTraceAdminStoreAction;
use App\Modules\Trace\Domain\Actions\Mutations\DeleteTraceAdminStoreAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceAdminStoreAction;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceAdminStoreCreateRequest;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceAdminStoreIndexRequest;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceAdminStoreResource;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceAdminStoresResource;

readonly class TraceAdminStoreController
{
    public function __construct(
        private FindTraceAdminStoreAction $findTraceAdminStoreAction,
        private CreateTraceAdminStoreAction $createTraceAdminStoreAction,
        private DeleteTraceAdminStoreAction $deleteTraceAdminStoreAction
    ) {
    }

    public function index(TraceAdminStoreIndexRequest $request): TraceAdminStoresResource
    {
        $validated = $request->validated();

        $pagination = $this->findTraceAdminStoreAction->handle(
            page: ArrayValueGetter::int($validated, 'page'),
            version: ArrayValueGetter::int($validated, 'version'),
            searchQuery: ArrayValueGetter::stringNull($validated, 'search_query'),
            auto: ArrayValueGetter::bool($validated, 'auto'),
        );

        return new TraceAdminStoresResource($pagination);
    }

    public function create(TraceAdminStoreCreateRequest $request): TraceAdminStoreResource
    {
        $validated = $request->validated();

        $store = $this->createTraceAdminStoreAction->handle(
            title: ArrayValueGetter::string($validated, 'title'),
            storeVersion: ArrayValueGetter::int($validated, 'store_version'),
            storeData: ArrayValueGetter::string($validated, 'store_data'),
            auto: ArrayValueGetter::bool($validated, 'auto'),
        );

        return new TraceAdminStoreResource($store);
    }

    public function delete(string $id): void
    {
        $this->deleteTraceAdminStoreAction->handle($id);
    }
}
