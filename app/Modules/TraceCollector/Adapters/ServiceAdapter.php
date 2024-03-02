<?php

namespace App\Modules\TraceCollector\Adapters;

use App\Models\Services\Service;
use App\Modules\Services\Http\Middlewares\AuthServiceMiddleware;
use App\Modules\Services\Http\RequestServiceContainer;
use Illuminate\Contracts\Foundation\Application;

readonly class ServiceAdapter
{
    public function __construct(private Application $app)
    {
    }

    public function getAuthMiddleware(): string
    {
        return AuthServiceMiddleware::class;
    }

    public function getService(): ?Service
    {
        return $this->app->make(RequestServiceContainer::class)->getService();
    }
}
