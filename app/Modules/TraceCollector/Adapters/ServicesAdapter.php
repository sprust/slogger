<?php

namespace App\Modules\TraceCollector\Adapters;

use App\Models\Services\Service;
use App\Modules\Service\Http\Middlewares\AuthServiceMiddleware;
use App\Modules\Service\Http\ServiceContainer;
use Illuminate\Contracts\Foundation\Application;

readonly class ServicesAdapter
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
        return $this->app->make(ServiceContainer::class)->getService();
    }
}
