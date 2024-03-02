<?php

namespace App\Modules\Service\Repository;

use App\Models\Services\Service;
use App\Modules\Service\Dto\Parameters\ServiceCreateParameters;
use Illuminate\Support\Str;

class ServiceRepository implements ServiceRepositoryInterface
{
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
