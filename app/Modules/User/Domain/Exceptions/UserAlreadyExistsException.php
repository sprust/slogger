<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Exceptions;

use Exception;

class UserAlreadyExistsException extends Exception
{
    public function __construct(string $email)
    {
        parent::__construct("User with email [$email] already exists");
    }
}
