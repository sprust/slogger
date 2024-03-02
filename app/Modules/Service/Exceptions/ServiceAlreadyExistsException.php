<?php

namespace App\Modules\Service\Exceptions;

use Exception;

class ServiceAlreadyExistsException extends Exception
{
    public function __construct(string $name)
    {
        parent::__construct("Service with name [$name] already exists");
    }
}
