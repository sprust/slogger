<?php

namespace App\Modules\Service\Adapters\Auth;

use App\Modules\Auth\Http\Middlewares\AuthMiddleware;

readonly class AuthAdapter
{
    public function getAuthMiddleware(): string
    {
        return AuthMiddleware::class;
    }
}
