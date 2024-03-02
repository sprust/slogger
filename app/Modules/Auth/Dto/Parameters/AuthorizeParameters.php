<?php

namespace App\Modules\Auth\Dto\Parameters;

class AuthorizeParameters
{
    public function __construct(
        public string $token
    ) {
    }
}
