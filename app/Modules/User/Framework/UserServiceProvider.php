<?php

namespace App\Modules\User\Framework;

use App\Modules\Common\Framework\BaseServiceProvider;
use App\Modules\User\Domain\Actions\CreateUserAction;
use App\Modules\User\Domain\Actions\FindUserByEmailAction;
use App\Modules\User\Domain\Actions\FindUserByTokenAction;
use App\Modules\User\Domain\Actions\Interfaces\CreateUserActionInterface;
use App\Modules\User\Domain\Actions\Interfaces\FindUserByEmailActionInterface;
use App\Modules\User\Domain\Actions\Interfaces\FindUserByTokenActionInterface;
use App\Modules\User\Framework\Commands\CreateUserCommand;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Repositories\UserRepositoryInterface;

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
