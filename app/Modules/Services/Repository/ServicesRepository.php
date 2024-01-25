<?php

namespace App\Modules\Services\Repository;

use App\Models\Services\Service;
use App\Modules\Services\Repository\Exceptions\ServiceAlreadyExistsException;
use App\Modules\Services\Repository\Parameters\ServiceCreateParameters;
use Illuminate\Support\Str;
use Throwable;

class ServicesRepository
{
    /**
     * @throws ServiceAlreadyExistsException
     * @throws Throwable
     */
    public function create(ServiceCreateParameters $parameters): Service
    {
        $newService = new Service();

        $name = Str::slug($parameters->name);

        $exists = Service::query()->where('name', $name)->exists();

        if ($exists) {
            throw new ServiceAlreadyExistsException($parameters->name);
        }

        $newService->name      = $name;
        $newService->api_token = Str::random(50);

        $newService->saveOrFail();

        return $newService;
    }
}
