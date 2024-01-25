<?php

namespace App\Modules\Services\Services;

use App\Models\Services\Service;
use App\Modules\Services\Dto\Parameters\ServiceCreateParameters;
use App\Modules\Services\Exceptions\ServiceAlreadyExistsException;
use App\Modules\Services\Repository\ServicesRepositoryInterface;

readonly class ServicesService
{
    public function __construct(private ServicesRepositoryInterface $servicesRepository)
    {
    }

    /**
     * @throws ServiceAlreadyExistsException
     */
    public function create(ServiceCreateParameters $parameters): Service
    {
        if ($this->servicesRepository->isExistByUniqueKey($parameters->uniqueKey)) {
            throw new ServiceAlreadyExistsException($parameters->name);
        }

        return $this->servicesRepository->create($parameters);
    }

    public function findByToken(string $token): ?Service
    {
        return $this->servicesRepository->findByToken($token);
    }
}
