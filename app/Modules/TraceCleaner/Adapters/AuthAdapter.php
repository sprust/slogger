<?php

namespace App\Modules\TraceCleaner\Adapters;

use App\Modules\Auth\Framework\Http\Middlewares\AuthMiddleware;

readonly class AuthAdapter
{
    public function getAuthMiddleware(): string
    {
        return AuthMiddleware::class;
    }
}
