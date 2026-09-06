<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure;

use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\User\Domain\Actions\CreateUserAction;
use App\Modules\User\Domain\Actions\CreateUserTokenAction;
use App\Modules\User\Domain\Actions\DeleteExpiredUserTokensAction;
use App\Modules\User\Domain\Actions\DeleteUserTokenAction;
use App\Modules\User\Domain\Actions\FindUserByEmailAction;
use App\Modules\User\Domain\Actions\FindUserByTokenAction;
use App\Modules\User\Domain\Actions\TouchUserTokenAction;
use App\Modules\User\Domain\Services\UserTokenLifetimeService;
use App\Modules\User\Infrastructure\Commands\CreateUserCommand;
use App\Modules\User\Infrastructure\Commands\DeleteExpiredUserTokensCommand;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Repositories\UserTokenRepository;

class UserServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->commands([
            CreateUserCommand::class,
            DeleteExpiredUserTokensCommand::class,
        ]);
    }

    protected function getContracts(): array
    {
        return [
            // repositories
            UserRepository::class,
            UserTokenRepository::class,
            // services
            UserTokenLifetimeService::class,
            // actions
            CreateUserAction::class,
            CreateUserTokenAction::class,
            DeleteExpiredUserTokensAction::class,
            DeleteUserTokenAction::class,
            FindUserByEmailAction::class,
            FindUserByTokenAction::class,
            TouchUserTokenAction::class,
        ];
    }
}
