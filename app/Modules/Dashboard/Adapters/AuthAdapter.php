<?php

namespace App\Modules\Dashboard\Adapters;

use App\Modules\Auth\Http\Middlewares\AuthMiddleware;

readonly class AuthAdapter
{
    public function getAuthMiddleware(): string
    {
        return AuthMiddleware::class;
    }
}
