<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UseCases;

use App\Modules\User\Entities\UserObject;
use App\Modules\User\Domain\Actions\FindUsersAction;

readonly class FindUsersByAdminUseCase
{
    public function __construct(private FindUsersAction $findUsersAction)
    {
    }

    /**
     * @return UserObject[]
     */
    public function handle(): array
    {
        return $this->findUsersAction->handle();
    }
}
