<?php

namespace App\Modules\TracesCollector\Adapters;

use App\Models\Services\Service;
use App\Modules\Services\Http\Middlewares\AuthServiceMiddleware;
use App\Modules\Services\Http\RequestServiceContainer;
use Illuminate\Contracts\Foundation\Application;

readonly class TraceServicesHttpAdapter
{
    public function __construct(private Application $app)
    {
    }

    public function getRequestMiddleware(): string
    {
        return AuthServiceMiddleware::class;
    }

    public function getService(): ?Service
    {
        return $this->app->make(RequestServiceContainer::class)->getService();
    }
}
