<?php

namespace App\Modules\Services\Adapters;

use App\Models\Services\Service;
use App\Modules\Services\Http\Middlewares\AuthServiceMiddleware;
use App\Modules\Services\Http\RequestServiceContainer;
use Illuminate\Foundation\Application;

readonly class ServicesHttpAdapter
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
