<?php

declare(strict_types=1);

namespace App\Modules\Service\Repositories;

use App\Models\Services\Service;
use App\Modules\Service\Entities\ServiceObject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ServiceRepository
{
    /**
     * @param int[]|null $ids
     *
     * @return ServiceObject[]
     */
    public function find(?array $ids = null): array
    {
        return Service::query()
            ->when(
                !is_null($ids),
                fn(Builder $query) => $query->whereIn('id', $ids)
            )
            ->select([
                'id',
                'name',
                'api_token',
            ])
            ->get()
            ->map(
                fn(Service $service) => $this->makeObjectByModel($service)
            )
            ->toArray();
    }

    public function create(string $name, string $uniqueKey): ServiceObject
    {
        $newService = new Service();

        $newService->name       = $name;
        $newService->unique_key = $uniqueKey;
        $newService->api_token  = Str::random(50);

        $newService->saveOrFail();

        return $this->makeObjectByModel($newService);
    }

    public function isExistByUniqueKey(string $uniqueKey): bool
    {
        return Service::query()->where('unique_key', $uniqueKey)->exists();
    }

    private function makeObjectByModel(Service $service): ServiceObject
    {
        return new ServiceObject(
            id: $service->id,
            name: $service->name,
            apiToken: $service->api_token
        );
    }
}
