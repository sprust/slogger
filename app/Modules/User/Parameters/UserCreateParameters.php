<?php

declare(strict_types=1);

namespace App\Modules\User\Parameters;

readonly class UserCreateParameters
{
    public function __construct(
        public string $firstName,
        public ?string $lastName,
        public string $email,
        public string $password
    ) {
    }
}
