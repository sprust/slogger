<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\Exceptions;

class ServiceNotFoundException extends AbstractServiceException
{
    public function __construct(int $serviceId)
    {
        parent::__construct("Service [$serviceId] not found");
    }
}
