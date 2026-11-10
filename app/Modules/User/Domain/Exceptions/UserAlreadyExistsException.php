<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Exceptions;

class UserAlreadyExistsException extends AbstractUserException
{
    public function __construct(string $email)
    {
        parent::__construct("User with email [$email] already exists");
    }
}
