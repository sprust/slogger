<?php

namespace App\Modules\User;

use App\Modules\User\Api\UserApi;
use App\Modules\User\Commands\CreateUserCommand;
use App\Modules\User\Repository\UserRepository;
use App\Modules\User\Repository\UserRepositoryInterface;
use App\Modules\User\Services\UserService;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(UserRepositoryInterface::class, UserRepository::class);
        $this->app->singleton(UserService::class);
        $this->app->singleton(UserApi::class);

        $this->commands([
            CreateUserCommand::class,
        ]);
    }
}
