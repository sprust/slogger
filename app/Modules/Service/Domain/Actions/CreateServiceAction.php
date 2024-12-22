<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\Actions;

use App\Modules\Service\Contracts\Actions\CreateServiceActionInterface;
use App\Modules\Service\Contracts\Repositories\ServiceRepositoryInterface;
use App\Modules\Service\Domain\Exceptions\ServiceAlreadyExistsException;
use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Service\Parameters\ServiceCreateParameters;
use Illuminate\Support\Str;

readonly class CreateServiceAction implements CreateServiceActionInterface
{
    public function __construct(
        private ServiceRepositoryInterface $serviceRepository
    ) {
    }

    public function handle(ServiceCreateParameters $parameters): ServiceObject
    {
        $uniqueKey = Str::slug($parameters->name);

        if ($this->serviceRepository->isExistByUniqueKey($uniqueKey)) {
            throw new ServiceAlreadyExistsException($parameters->name);
        }

        return $this->serviceRepository->create(
            name: $parameters->name,
            uniqueKey: $uniqueKey,
        );
    }
}
