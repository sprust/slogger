<?php

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Contracts\Actions\FindUserByTokenActionInterface;
use App\Modules\Auth\Contracts\Actions\LoginActionInterface;
use App\Modules\Auth\Domain\Actions\FindUserByTokenAction;
use App\Modules\Auth\Domain\Actions\LoginAction;
use App\Modules\Common\Infrastructure\BaseServiceProvider;

class AuthServiceProvider extends BaseServiceProvider
{
    protected function getContracts(): array
    {
        return [
            FindUserByTokenActionInterface::class => FindUserByTokenAction::class,
            LoginActionInterface::class           => LoginAction::class,
        ];
    }
}
