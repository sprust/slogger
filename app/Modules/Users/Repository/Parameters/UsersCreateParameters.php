<?php

namespace App\Modules\Users\Repository\Parameters;

readonly class UsersCreateParameters
{
    public function __construct(
        public string $firstName,
        public ?string $lastName,
        public string $email,
        public string $password
    ) {
    }
}
