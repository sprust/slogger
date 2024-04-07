<?php

namespace App\Modules\Auth\Domain\Entities\Parameters;

class LoginParameters
{
    public function __construct(
        public string $email,
        public string $password
    ) {
    }
}
