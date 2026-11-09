<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\User\Domain\UseCases\CreateUserByAdminUseCase;
use App\Modules\User\Domain\UseCases\FindUsersByAdminUseCase;
use App\Modules\User\Domain\UseCases\UpdateUserByAdminUseCase;
use App\Modules\User\Infrastructure\Http\Requests\CreateUserRequest;
use App\Modules\User\Infrastructure\Http\Requests\UpdateUserRequest;
use App\Modules\User\Infrastructure\Http\Resources\UserResource;
use App\Modules\User\Parameters\UserCreateParameters;
use App\Modules\User\Parameters\UserUpdateParameters;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class UserController
{
    public function __construct(private FindUsersByAdminUseCase $findUsersByAdminUseCase, private CreateUserByAdminUseCase $createUserByAdminUseCase, private UpdateUserByAdminUseCase $updateUserByAdminUseCase)
    {
    }

    #[OaListItemTypeAttribute(UserResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection($this->findUsersByAdminUseCase->handle());
    }

    public function create(CreateUserRequest $request): UserResource
    {
        $validated = $request->validated();
        $user      = $this->createUserByAdminUseCase->handle(
            new UserCreateParameters(
                firstName: ArrayValueGetter::string(
                    data: $validated,
                    key: 'first_name',
                ),
                lastName: ArrayValueGetter::stringNull(
                    data: $validated,
                    key: 'last_name',
                ),
                email: ArrayValueGetter::string(
                    data: $validated,
                    key: 'email',
                ),
                password: ArrayValueGetter::string(
                    data: $validated,
                    key: 'password',
                ),
            )
        );

        return new UserResource($user);
    }

    public function update(int $id, UpdateUserRequest $request): UserResource
    {
        $validated = $request->validated();
        $user      = $this->updateUserByAdminUseCase->handle(
            new UserUpdateParameters(
                id: $id,
                firstName: ArrayValueGetter::string(
                    data: $validated,
                    key: 'first_name',
                ),
                lastName: ArrayValueGetter::stringNull(
                    data: $validated,
                    key: 'last_name',
                ),
            )
        );

        return new UserResource($user);
    }
}
