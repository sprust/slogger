<?php

namespace App\Modules\Users\Parameters;

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
