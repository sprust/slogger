<?php

declare(strict_types=1);

namespace App\Modules\Auth\Parameters;

class LoginParameters
{
    public function __construct(
        public string $email,
        public string $password
    ) {
    }
}
