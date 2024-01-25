<?php

namespace App\Modules\Services\Repository;

use App\Models\Services\Service;
use App\Modules\Services\Dto\Parameters\ServiceCreateParameters;
use Illuminate\Support\Str;

class ServicesRepository implements ServicesRepositoryInterface
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
