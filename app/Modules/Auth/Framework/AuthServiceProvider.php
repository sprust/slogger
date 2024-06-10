<?php

namespace App\Modules\Auth\Framework;

use App\Modules\Auth\Domain\Actions\FindUserByTokenAction;
use App\Modules\Auth\Domain\Actions\Interfaces\FindUserByTokenActionInterface;
use App\Modules\Auth\Domain\Actions\Interfaces\LoginActionInterface;
use App\Modules\Auth\Domain\Actions\LoginAction;
use App\Modules\Common\Framework\BaseServiceProvider;

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
