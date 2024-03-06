<?php

namespace App\Modules\Service\Repository;

use App\Models\Services\Service;
use App\Modules\Service\Dto\Objects\ServiceDetailObject;
use App\Modules\Service\Dto\Objects\ServiceObject;
use App\Modules\Service\Dto\Parameters\ServiceCreateParameters;
use Illuminate\Support\Str;

class ServiceRepository implements ServiceRepositoryInterface
{
    public function find(): array
    {
        return Service::query()
            ->select([
                'id',
                'name',
            ])
            ->get()
            ->map(
                fn(Service $service) => new ServiceObject(
                    id: $service->id,
                    name: $service->name,
                )
            )
            ->toArray();
    }

    public function findById(int $id): ?ServiceDetailObject
    {
        /** @var Service|null $service */
        $service = Service::query()->find($id);

        if (!$service) {
            return null;
        }

        return new ServiceDetailObject(
            id: $service->id,
            name: $service->name,
            apiToken: $service->api_token,
        );
    }

    public function create(ServiceCreateParameters $parameters): Service
    {
        $newService = new Service();

        $newService->name       = $parameters->name;
        $newService->unique_key = $parameters->uniqueKey;
        $newService->api_token  = Str::random(50);

        $newService->saveOrFail();

        return $newService;
    }

    public function findByToken(string $token): ?Service
    {
        return Service::query()->where('api_token', $token)->first();
    }

    public function isExistByUniqueKey(string $uniqueKey): bool
    {
        return Service::query()->where('unique_key', $uniqueKey)->exists();
    }
}
