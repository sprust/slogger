<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Exceptions;

use Exception;

class UserNotFoundException extends Exception
{
    public function __construct(int $userId)
    {
        parent::__construct("User [$userId] not found");
    }
}
