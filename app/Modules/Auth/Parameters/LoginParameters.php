<?php

namespace App\Modules\Auth\Parameters;

class LoginParameters
{
    public function __construct(
        public string $email,
        public string $password
    ) {
    }
}
