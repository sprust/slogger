<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UseCases;

use App\Modules\User\Domain\Actions\CreateUserAction;
use App\Modules\User\Domain\Actions\FindUserAction;
use App\Modules\User\Domain\Exceptions\UserNotFoundException;
use App\Modules\User\Domain\Exceptions\UserAlreadyExistsException;
use App\Modules\User\Entities\UserObject;
use App\Modules\User\Parameters\UserCreateParameters;

readonly class CreateUserByAdminUseCase
{
    public function __construct(private CreateUserAction $createUserAction, private FindUserAction $findUserAction)
    {
    }

    /**
     * @throws UserNotFoundException
     * @throws UserAlreadyExistsException
     */
    public function handle(UserCreateParameters $parameters): UserObject
    {
        $id = $this->createUserAction->handle($parameters);

        return $this->findUserAction->handle($id) ?? throw new UserNotFoundException($id);
    }
}
