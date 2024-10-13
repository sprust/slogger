<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceAdminStore;
use App\Modules\Common\Entities\PaginationInfoObject;
use App\Modules\Trace\Contracts\Repositories\TraceAdminStoreRepositoryInterface;
use App\Modules\Trace\Entities\Store\TraceAdminStoreObject;
use App\Modules\Trace\Entities\Store\TraceAdminStoresPaginationObject;
use Illuminate\Database\Eloquent\Builder;

class TraceAdminStoreRepository implements TraceAdminStoreRepositoryInterface
{
    public function create(
        string $title,
        int $storeVersion,
        string $storeDataHash,
        string $storeData,
    ): TraceAdminStoreObject {
        $store = new TraceAdminStore();

        $store->title         = $title;
        $store->storeVersion  = $storeVersion;
        $store->storeDataHash = $storeDataHash;
        $store->storeData     = $storeData;

        $store->save();

        return $this->modelToObject($store);
    }

    public function find(
        int $page,
        int $perPage,
        int $version,
        ?string $searchQuery = null
    ): TraceAdminStoresPaginationObject {
        $pagination = TraceAdminStore::query()
            ->where('storeVersion', $version)
            ->when(
                $searchQuery,
                fn(Builder $builder) => $builder->where('title', 'like', "%$searchQuery%")
            )
            ->orderByDesc('createdAt')
            ->paginate(perPage: $perPage, page: $page);

        return new TraceAdminStoresPaginationObject(
            items: array_map(
                fn(TraceAdminStore $store) => $this->modelToObject($store),
                $pagination->items()
            ),
            paginationInfo: new PaginationInfoObject(
                total: $pagination->total(),
                perPage: $pagination->perPage(),
                currentPage: $pagination->currentPage()
            )
        );
    }

    public function delete(string $id): bool
    {
        return (bool) TraceAdminStore::query()->where('_id', $id)->delete();
    }

    private function modelToObject(TraceAdminStore $store): TraceAdminStoreObject
    {
        return new TraceAdminStoreObject(
            id: $store->_id,
            title: $store->title,
            storeVersion: $store->storeVersion,
            storeDataHash: $store->storeDataHash,
            storeData: $store->storeData,
            createdAt: $store->createdAt,
        );
    }
}
