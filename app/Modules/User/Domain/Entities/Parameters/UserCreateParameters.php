<?php

namespace App\Modules\User\Domain\Entities\Parameters;

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
