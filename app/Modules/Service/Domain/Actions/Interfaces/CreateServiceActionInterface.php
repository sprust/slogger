<?php

namespace App\Modules\Service\Domain\Actions\Interfaces;

use App\Modules\Service\Domain\Entities\Objects\ServiceObject;
use App\Modules\Service\Domain\Entities\Parameters\ServiceCreateParameters;
use App\Modules\Service\Domain\Exceptions\ServiceAlreadyExistsException;

interface CreateServiceActionInterface
{
    /**
     * @throws ServiceAlreadyExistsException
     */
    public function handle(ServiceCreateParameters $parameters): ServiceObject;
}
