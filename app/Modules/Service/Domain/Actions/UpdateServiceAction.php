<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\Actions;

use App\Modules\Service\Domain\Exceptions\ServiceAlreadyExistsException;
use App\Modules\Service\Domain\Exceptions\ServiceNotFoundException;
use App\Modules\Service\Parameters\ServiceUpdateParameters;
use App\Modules\Service\Repositories\ServiceRepository;
use Illuminate\Support\Str;

readonly class UpdateServiceAction
{
    public function __construct(
        private ServiceRepository $serviceRepository
    ) {
    }

    /**
     * @throws ServiceNotFoundException
     * @throws ServiceAlreadyExistsException
     */
    public function handle(ServiceUpdateParameters $parameters): void
    {
        if (is_null($this->serviceRepository->findOneById($parameters->id))) {
            throw new ServiceNotFoundException($parameters->id);
        }

        if ($this->serviceRepository->isExistByUniqueKey(Str::slug($parameters->name), exceptId: $parameters->id)) {
            throw new ServiceAlreadyExistsException($parameters->name);
        }

        $this->serviceRepository->updateName(
            id: $parameters->id,
            name: $parameters->name
        );
    }
}
