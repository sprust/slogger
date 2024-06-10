<?php

namespace App\Modules\Auth\Framework;

use App\Modules\Auth\Domain\Actions\FindUserByTokenAction;
use App\Modules\Auth\Domain\Actions\Interfaces\FindUserByTokenActionInterface;
use App\Modules\Auth\Domain\Actions\Interfaces\LoginActionInterface;
use App\Modules\Auth\Domain\Actions\LoginAction;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(
            FindUserByTokenActionInterface::class,
            FindUserByTokenAction::class
        );
        $this->app->singleton(
            LoginActionInterface::class,
            LoginAction::class
        );
    }
}
