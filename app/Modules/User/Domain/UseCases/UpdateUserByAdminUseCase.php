<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UseCases;

use App\Modules\User\Domain\Actions\UpdateUserAction;
use App\Modules\User\Domain\Actions\FindUserAction;
use App\Modules\User\Domain\Exceptions\UserNotFoundException;
use App\Modules\User\Domain\Exceptions\UserAlreadyExistsException;
use App\Modules\User\Entities\UserObject;
use App\Modules\User\Parameters\UserUpdateParameters;

readonly class UpdateUserByAdminUseCase
{
    public function __construct(private UpdateUserAction $updateUserAction, private FindUserAction $findUserAction)
    {
    }

    /**
     * @throws UserNotFoundException
     * @throws UserAlreadyExistsException
     */
    public function handle(UserUpdateParameters $parameters): UserObject
    {
        $this->updateUserAction->handle($parameters);

        $id = $parameters->id;

        return $this->findUserAction->handle($id) ?? throw new UserNotFoundException($id);
    }
}
