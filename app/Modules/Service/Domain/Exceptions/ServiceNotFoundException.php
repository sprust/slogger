<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\Exceptions;

use Exception;

class ServiceNotFoundException extends Exception
{
    public function __construct(int $serviceId)
    {
        parent::__construct("Service [$serviceId] not found");
    }
}
