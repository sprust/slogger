<?php

namespace App\Modules\TracesAggregator\Adapters;

use App\Modules\Auth\Http\Middlewares\AuthMiddleware;

readonly class TracesAggregatorAuthAdapter
{
    public function getAuthMiddleware(): string
    {
        return AuthMiddleware::class;
    }
}
