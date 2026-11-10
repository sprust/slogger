<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Domain\UseCases\CreateTraceAdminStoreByAdminUseCase;
use App\Modules\Trace\Domain\UseCases\DeleteTraceAdminStoreByAdminUseCase;
use App\Modules\Trace\Domain\UseCases\FindTraceAdminStoreByAdminUseCase;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceAdminStoreCreateRequest;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceAdminStoreIndexRequest;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceAdminStoreResource;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceAdminStoresResource;

readonly class TraceAdminStoreController
{
    public function __construct(
        private FindTraceAdminStoreByAdminUseCase $findTraceAdminStoreByAdminUseCase,
        private CreateTraceAdminStoreByAdminUseCase $createTraceAdminStoreByAdminUseCase,
        private DeleteTraceAdminStoreByAdminUseCase $deleteTraceAdminStoreByAdminUseCase
    ) {
    }

    public function index(TraceAdminStoreIndexRequest $request): TraceAdminStoresResource
    {
        $validated  = $request->validated();
        $pagination = $this->findTraceAdminStoreByAdminUseCase->handle(
            page: ArrayValueGetter::int(
                data: $validated,
                key: 'page',
            ),
            version: ArrayValueGetter::int(
                data: $validated,
                key: 'version',
            ),
            searchQuery: ArrayValueGetter::stringNull(
                data: $validated,
                key: 'search_query',
            ),
            auto: ArrayValueGetter::bool(
                data: $validated,
                key: 'auto',
            ),
        );

        return new TraceAdminStoresResource($pagination);
    }

    public function create(TraceAdminStoreCreateRequest $request): TraceAdminStoreResource
    {
        $validated = $request->validated();

        $store = $this->createTraceAdminStoreByAdminUseCase->handle(
            title: ArrayValueGetter::string(
                data: $validated,
                key: 'title',
            ),
            storeVersion: ArrayValueGetter::int(
                data: $validated,
                key: 'store_version',
            ),
            storeData: ArrayValueGetter::string(
                data: $validated,
                key: 'store_data',
            ),
            auto: ArrayValueGetter::bool(
                data: $validated,
                key: 'auto',
            ),
        );

        return new TraceAdminStoreResource($store);
    }

    public function delete(string $id): void
    {
        $this->deleteTraceAdminStoreByAdminUseCase->handle($id);
    }
}
