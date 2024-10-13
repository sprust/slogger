<?php

namespace App\Modules\Service\Contracts\Actions;

use App\Modules\Service\Domain\Exceptions\ServiceAlreadyExistsException;
use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Service\Parameters\ServiceCreateParameters;

interface CreateServiceActionInterface
{
    /**
     * @throws ServiceAlreadyExistsException
     */
    public function handle(ServiceCreateParameters $parameters): ServiceObject;
}
