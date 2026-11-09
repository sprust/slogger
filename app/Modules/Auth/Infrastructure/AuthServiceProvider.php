<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Infrastructure\Exceptions\AuthExceptionManager;
use App\Modules\Auth\Domain\Actions\FindUserByTokenAction;
use App\Modules\Auth\Domain\Actions\LoginAction;
use App\Modules\Common\Infrastructure\BaseServiceProvider;

class AuthServiceProvider extends BaseServiceProvider
{
    protected function getContracts(): array
    {
        return [
            FindUserByTokenAction::class,
            LoginAction::class,
        ];
    }

    protected function getExceptionManagers(): array
    {
        return [
            AuthExceptionManager::class,
        ];
    }
}
