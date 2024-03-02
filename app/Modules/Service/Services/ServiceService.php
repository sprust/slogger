<?php

namespace App\Modules\Service\Services;

use App\Models\Services\Service;
use App\Modules\Service\Dto\Parameters\ServiceCreateParameters;
use App\Modules\Service\Exceptions\ServiceAlreadyExistsException;
use App\Modules\Service\Repository\ServiceRepositoryInterface;

readonly class ServiceService
{
    public function __construct(private ServiceRepositoryInterface $serviceRepository)
    {
    }

    /**
     * @throws ServiceAlreadyExistsException
     */
    public function create(ServiceCreateParameters $parameters): Service
    {
        if ($this->serviceRepository->isExistByUniqueKey($parameters->uniqueKey)) {
            throw new ServiceAlreadyExistsException($parameters->name);
        }

        return $this->serviceRepository->create($parameters);
    }

    public function findByToken(string $token): ?Service
    {
        return $this->serviceRepository->findByToken($token);
    }
}
