<?php

namespace App\Modules\Service\Repositories;

use App\Models\Services\Service;
use App\Modules\Service\Contracts\Repositories\ServiceRepositoryInterface;
use App\Modules\Service\Entities\ServiceObject;
use Illuminate\Support\Str;

class ServiceRepository implements ServiceRepositoryInterface
{
    public function find(): array
    {
        return Service::query()
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

    public function findByToken(string $token): ?ServiceObject
    {
        /** @var Service|null $service */
        $service = Service::query()->where('api_token', $token)->first();

        if (!$service) {
            return null;
        }

        return $this->makeObjectByModel($service);
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
