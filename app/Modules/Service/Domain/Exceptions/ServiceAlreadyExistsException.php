<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\Exceptions;

use Exception;

class ServiceAlreadyExistsException extends Exception
{
    public function __construct(string $name)
    {
        parent::__construct("Service with name [$name] already exists");
    }
}
