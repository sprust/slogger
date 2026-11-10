<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\UseCases;

use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Service\Parameters\ServiceCreateParameters;
use App\Modules\Service\Domain\Actions\CreateServiceAction;

readonly class CreateServiceByAdminUseCase
{
    public function __construct(private CreateServiceAction $createServiceAction)
    {
    }

    public function handle(ServiceCreateParameters $parameters): ServiceObject
    {
        return $this->createServiceAction->handle($parameters);
    }
}
