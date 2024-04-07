<?php

namespace App\Modules\Service\Domain\Actions;

use App\Modules\Service\Domain\Entities\Objects\ServiceObject;
use App\Modules\Service\Domain\Entities\Parameters\ServiceCreateParameters;
use App\Modules\Service\Domain\Entities\Transports\ServiceTransport;
use App\Modules\Service\Domain\Exceptions\ServiceAlreadyExistsException;
use App\Modules\Service\Repositories\ServiceRepositoryInterface;

readonly class CreateServiceAction
{
    public function __construct(
        private ServiceRepositoryInterface $serviceRepository
    ) {
    }

    /**
     * @throws ServiceAlreadyExistsException
     */
    public function handle(ServiceCreateParameters $parameters): ServiceObject
    {
        if ($this->serviceRepository->isExistByUniqueKey($parameters->uniqueKey)) {
            throw new ServiceAlreadyExistsException($parameters->name);
        }

        return ServiceTransport::toObject(
            $this->serviceRepository->create($parameters)
        );
    }
}
