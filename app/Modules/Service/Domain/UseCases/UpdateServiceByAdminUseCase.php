<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\UseCases;

use App\Modules\Service\Domain\Actions\UpdateServiceAction;
use App\Modules\Service\Domain\Actions\FindServiceAction;
use App\Modules\Service\Domain\Exceptions\ServiceNotFoundException;
use App\Modules\Service\Domain\Exceptions\ServiceAlreadyExistsException;
use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Service\Parameters\ServiceUpdateParameters;

readonly class UpdateServiceByAdminUseCase
{
    public function __construct(
        private UpdateServiceAction $updateServiceAction,
        private FindServiceAction $findServiceAction
    ) {
    }

    /**
     * @throws ServiceNotFoundException
     * @throws ServiceAlreadyExistsException
     */
    public function handle(ServiceUpdateParameters $parameters): ServiceObject
    {
        $this->updateServiceAction->handle($parameters);

        $id = $parameters->id;

        return $this->findServiceAction->handle($id) ?? throw new ServiceNotFoundException($id);
    }
}
