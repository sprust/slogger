<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\Actions;

use App\Modules\Service\Domain\Exceptions\ServiceAlreadyExistsException;
use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Service\Parameters\ServiceCreateParameters;
use App\Modules\Service\Repositories\ServiceRepository;
use Illuminate\Support\Str;

readonly class CreateServiceAction
{
    public function __construct(
        private ServiceRepository $serviceRepository
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
