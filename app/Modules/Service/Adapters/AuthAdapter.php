<?php

namespace App\Modules\Service\Adapters;

use App\Modules\Auth\Http\Middlewares\AuthMiddleware;

readonly class AuthAdapter
{
    public function getAuthMiddleware(): string
    {
        return AuthMiddleware::class;
    }
}
