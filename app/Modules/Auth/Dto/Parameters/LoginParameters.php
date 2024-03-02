<?php

namespace App\Modules\Auth\Dto\Parameters;

class LoginParameters
{
    public function __construct(
        public string $email,
        public string $password
    ) {
    }
}
