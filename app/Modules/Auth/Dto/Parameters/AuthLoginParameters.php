<?php

namespace App\Modules\Auth\Dto\Parameters;

class AuthLoginParameters
{
    public function __construct(
        public string $email,
        public string $password
    ) {
    }
}
