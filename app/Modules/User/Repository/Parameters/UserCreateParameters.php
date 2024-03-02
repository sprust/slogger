<?php

namespace App\Modules\User\Repository\Parameters;

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
