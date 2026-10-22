<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\User\Domain\Actions\CreateUserAction;
use App\Modules\User\Domain\Actions\FindUserAction;
use App\Modules\User\Domain\Actions\FindUsersAction;
use App\Modules\User\Domain\Actions\UpdateUserAction;
use App\Modules\User\Domain\Exceptions\UserAlreadyExistsException;
use App\Modules\User\Domain\Exceptions\UserNotFoundException;
use App\Modules\User\Infrastructure\Http\Requests\CreateUserRequest;
use App\Modules\User\Infrastructure\Http\Requests\UpdateUserRequest;
use App\Modules\User\Infrastructure\Http\Resources\UserResource;
use App\Modules\User\Parameters\UserCreateParameters;
use App\Modules\User\Parameters\UserUpdateParameters;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class UserController
{
    public function __construct(
        private FindUsersAction $findUsersAction,
        private FindUserAction $findUserAction,
        private CreateUserAction $createUserAction,
        private UpdateUserAction $updateUserAction
    ) {
    }

    #[OaListItemTypeAttribute(UserResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(
            $this->findUsersAction->handle()
        );
    }

    public function create(CreateUserRequest $request): UserResource
    {
        $validated = $request->validated();

        try {
            $userId = $this->createUserAction->handle(
                new UserCreateParameters(
                    firstName: ArrayValueGetter::string($validated, 'first_name'),
                    lastName: ArrayValueGetter::stringNull($validated, 'last_name'),
                    email: ArrayValueGetter::string($validated, 'email'),
                    password: ArrayValueGetter::string($validated, 'password')
                )
            );
        } catch (UserAlreadyExistsException $exception) {
            abort(ResponseFoundation::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }

        return $this->show($userId);
    }

    public function update(int $id, UpdateUserRequest $request): UserResource
    {
        $validated = $request->validated();

        try {
            $this->updateUserAction->handle(
                new UserUpdateParameters(
                    id: $id,
                    firstName: ArrayValueGetter::string($validated, 'first_name'),
                    lastName: ArrayValueGetter::stringNull($validated, 'last_name')
                )
            );
        } catch (UserNotFoundException $exception) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, $exception->getMessage());
        }

        return $this->show($id);
    }

    private function show(int $id): UserResource
    {
        $user = $this->findUserAction->handle($id);

        if (is_null($user)) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, "User [$id] not found");
        }

        return new UserResource($user);
    }
}
