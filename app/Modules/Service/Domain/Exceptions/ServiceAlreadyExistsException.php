<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\Exceptions;

class ServiceAlreadyExistsException extends AbstractServiceException
{
    public function __construct(string $name)
    {
        parent::__construct("Service with name [$name] already exists");
    }
}
