<?php

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Trace\Contracts\Actions\Mutations\CreateTraceAdminStoreActionInterface;
use App\Modules\Trace\Contracts\Actions\Mutations\DeleteTraceAdminStoreActionInterface;
use App\Modules\Trace\Contracts\Actions\Queries\FindTraceAdminStoreActionInterface;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceAdminStoreCreateRequest;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceAdminStoreIndexRequest;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceAdminStoreResource;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceAdminStoresResource;

readonly class TraceAdminStoreController
{
    public function __construct(
        private FindTraceAdminStoreActionInterface $findTraceAdminStoreAction,
        private CreateTraceAdminStoreActionInterface $createTraceAdminStoreAction,
        private DeleteTraceAdminStoreActionInterface $deleteTraceAdminStoreAction
    ) {
    }

    public function index(TraceAdminStoreIndexRequest $request): TraceAdminStoresResource
    {
        $validated = $request->validated();

        $pagination = $this->findTraceAdminStoreAction->handle(
            page: $validated['page'],
            version: $validated['version'],
            searchQuery: $validated['search_query'] ?? null,
        );

        return new TraceAdminStoresResource($pagination);
    }

    public function create(TraceAdminStoreCreateRequest $request): TraceAdminStoreResource
    {
        $validated = $request->validated();

        $store = $this->createTraceAdminStoreAction->handle(
            title: $validated['title'],
            storeVersion: $validated['store_version'],
            storeData: $validated['store_data'],
        );

        return new TraceAdminStoreResource($store);
    }

    public function delete(string $id): void
    {
        $this->deleteTraceAdminStoreAction->handle($id);
    }
}
