<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceAdminStore;
use App\Modules\Common\Repositories\PaginationInfoDto;
use App\Modules\Trace\Repositories\Dto\TraceAdminStoreDto;
use App\Modules\Trace\Repositories\Dto\TraceAdminStoresPaginationDto;
use App\Modules\Trace\Repositories\Interfaces\TraceAdminStoreRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TraceAdminStoreRepository implements TraceAdminStoreRepositoryInterface
{
    public function create(
        string $title,
        int $storeVersion,
        string $storeDataHash,
        string $storeData,
        int $creatorId,
        ?Carbon $usedAt
    ): TraceAdminStoreDto {
        $store = new TraceAdminStore();

        $store->title         = $title;
        $store->storeVersion  = $storeVersion;
        $store->storeDataHash = $storeDataHash;
        $store->storeData     = $storeData;
        $store->creatorId     = $creatorId;
        $store->usedAt        = $usedAt;

        $store->save();

        return $this->modelToDto($store);
    }

    public function find(
        int $page,
        int $perPage,
        int $version,
        ?string $searchQuery = null
    ): TraceAdminStoresPaginationDto {
        $pagination = TraceAdminStore::query()
            ->where('storeVersion', $version)
            ->when(
                $searchQuery,
                fn(Builder $builder) => $builder->where('title', 'like', "%$searchQuery%")
            )
            ->orderByDesc('createdAt')
            ->paginate(perPage: $perPage, page: $page);

        return new TraceAdminStoresPaginationDto(
            items: array_map(
                fn(TraceAdminStore $store) => $this->modelToDto($store),
                $pagination->items()
            ),
            paginationInfo: new PaginationInfoDto(
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

    private function modelToDto(TraceAdminStore $store): TraceAdminStoreDto
    {
        return new TraceAdminStoreDto(
            id: $store->_id,
            title: $store->title,
            storeVersion: $store->storeVersion,
            storeDataHash: $store->storeDataHash,
            storeData: $store->storeData,
            creatorId: $store->creatorId,
            usedAt: $store->usedAt,
            createdAt: $store->createdAt,
        );
    }
}
