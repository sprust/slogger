<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure;

use App\Modules\Common\Infrastructure\BaseServiceProvider;
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
            UserRepository::class,
            // actions
            CreateUserAction::class,
            FindUserByEmailAction::class,
            FindUserByTokenAction::class,
        ];
    }
}
