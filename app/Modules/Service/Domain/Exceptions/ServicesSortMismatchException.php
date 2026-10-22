<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\Exceptions;

use Exception;

class ServicesSortMismatchException extends Exception
{
    public function __construct()
    {
        parent::__construct('The sorted ids must be the ids of all services, each once');
    }
}
