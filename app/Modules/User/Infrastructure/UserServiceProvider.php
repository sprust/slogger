<?php

namespace App\Modules\User\Infrastructure;

use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\User\Contracts\Domain\CreateUserActionInterface;
use App\Modules\User\Contracts\Domain\FindUserByEmailActionInterface;
use App\Modules\User\Contracts\Domain\FindUserByTokenActionInterface;
use App\Modules\User\Contracts\Repositories\UserRepositoryInterface;
use App\Modules\User\Domain\Actions\CreateUserAction;
use App\Modules\User\Domain\Actions\FindUserByEmailAction;
use App\Modules\User\Domain\Actions\FindUserByTokenAction;
use App\Modules\User\Infrastructure\Commands\CreateUserCommand;
use App\Modules\User\Repositories\UserRepository;

class UserServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->commands([
            CreateUserCommand::class,
        ]);
    }

    protected function getContracts(): array
    {
        return [
            // repositories
            UserRepositoryInterface::class        => UserRepository::class,
            // actions
            CreateUserActionInterface::class      => CreateUserAction::class,
            FindUserByEmailActionInterface::class => FindUserByEmailAction::class,
            FindUserByTokenActionInterface::class => FindUserByTokenAction::class,
        ];
    }
}
