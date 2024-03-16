<?php

namespace App\Modules\Service\Repository;

use App\Models\Services\Service;
use App\Modules\Service\Repository\Dto\ServiceDto;
use App\Modules\Service\Services\Parameters\ServiceCreateParameters;
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
                fn(Service $service) => $this->makeDtoByModel($service)
            )
            ->toArray();
    }

    public function create(ServiceCreateParameters $parameters): ServiceDto
    {
        $newService = new Service();

        $newService->name       = $parameters->name;
        $newService->unique_key = $parameters->uniqueKey;
        $newService->api_token  = Str::random(50);

        $newService->saveOrFail();

        return $this->makeDtoByModel($newService);
    }

    public function findByToken(string $token): ?ServiceDto
    {
        /** @var Service|null $service */
        $service = Service::query()->where('api_token', $token)->first();

        if (!$service) {
            return null;
        }

        return $this->makeDtoByModel($service);
    }

    public function isExistByUniqueKey(string $uniqueKey): bool
    {
        return Service::query()->where('unique_key', $uniqueKey)->exists();
    }

    private function makeDtoByModel(Service $service): ServiceDto
    {
        return new ServiceDto(
            id: $service->id,
            name: $service->name,
            apiToken: $service->api_token
        );
    }
}
