<?php

namespace App\Modules\Service\Repositories;

use App\Models\Services\Service;
use App\Modules\Service\Repositories\Dto\ServiceDto;
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

    public function create(string $name, string $uniqueKey): ServiceDto
    {
        $newService = new Service();

        $newService->name       = $name;
        $newService->unique_key = $uniqueKey;
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
