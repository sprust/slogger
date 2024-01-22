<?php

namespace App\Modules\Projects\Repository\Exceptions;

use Exception;

class ProjectAlreadyExistsException extends Exception
{
    public function __construct(string $projectName)
    {
        parent::__construct("Project with name '$projectName' already exists");
    }
}
