<?php

namespace App\Modules\Service\Services;

use App\Modules\Service\Exceptions\ServiceAlreadyExistsException;
use App\Modules\Service\Repository\Dto\ServiceDto;
use App\Modules\Service\Repository\ServiceRepositoryInterface;
use App\Modules\Service\Services\Objects\ServiceObject;
use App\Modules\Service\Services\Parameters\ServiceCreateParameters;

readonly class ServiceService
{
    public function __construct(private ServiceRepositoryInterface $serviceRepository)
    {
    }

    /**
     * @return ServiceObject[]
     */
    public function find(): array
    {
        return array_map(
            fn(ServiceDto $dto) => $this->makeObjectByDto($dto),
            $this->serviceRepository->find()
        );
    }

    /**
     * @throws ServiceAlreadyExistsException
     */
    public function create(ServiceCreateParameters $parameters): ServiceObject
    {
        if ($this->serviceRepository->isExistByUniqueKey($parameters->uniqueKey)) {
            throw new ServiceAlreadyExistsException($parameters->name);
        }

        return $this->makeObjectByDto(
            $this->serviceRepository->create($parameters)
        );
    }

    public function findByToken(string $token): ?ServiceObject
    {
        /** @var ServiceDto|null $serviceDto */
        $serviceDto = $this->serviceRepository->findByToken($token);

        if (!$serviceDto) {
            return null;
        }

        return $this->makeObjectByDto($serviceDto);
    }

    private function makeObjectByDto(ServiceDto $dto): ServiceObject
    {
        return new ServiceObject(
            id: $dto->id,
            name: $dto->name,
            apiToken: $dto->apiToken
        );
    }
}
