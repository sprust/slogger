<?php

namespace App\Modules\Auth\Dto\Parameters;

class AuthAuthorizeParameters
{
    public function __construct(
        public string $token
    ) {
    }
}
