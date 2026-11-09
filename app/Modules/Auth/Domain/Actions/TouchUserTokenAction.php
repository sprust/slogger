<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Actions;

use App\Modules\User\Domain\Actions\TouchUserTokenAction as UserTouchUserTokenAction;
use App\Modules\Auth\Parameters\TokenParameters;

readonly class TouchUserTokenAction
{
    public function __construct(private UserTouchUserTokenAction $userTouchUserTokenAction)
    {
    }

    public function handle(TokenParameters $parameters): void
    {
        $this->userTouchUserTokenAction->handle($parameters->token);
    }
}
