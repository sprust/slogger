<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Exceptions;

class AuthenticationFailedException extends AbstractAuthException
{
    public function __construct()
    {
        parent::__construct('Authentication failed');
    }
}
