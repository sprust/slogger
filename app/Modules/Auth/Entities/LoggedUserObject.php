<?php

namespace App\Modules\Auth\Entities;

readonly class LoggedUserObject
{
    public function __construct(
        public int $id,
        public string $firstName,
        public ?string $lastName,
        public string $email,
        public string $apiToken
    ) {
    }
}
