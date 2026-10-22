<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Models\Users\User;
use App\Modules\User\Entities\UserDetailObject;
use App\Modules\User\Entities\UserObject;
use App\Modules\User\Parameters\UserCreateParameters;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    public function create(UserCreateParameters $parameters): int
    {
        $newUser = new User();

        $newUser->first_name = $parameters->firstName;
        $newUser->last_name  = $parameters->lastName;
        $newUser->email      = $parameters->email;
        $newUser->password   = Hash::make($parameters->password);

        $newUser->saveOrFail();

        return $newUser->id;
    }

    /**
     * @return UserObject[]
     */
    public function find(): array
    {
        return User::query()
            ->orderBy('id')
            ->get()
            ->map(fn(User $user) => $this->makeObjectByModel($user))
            ->all();
    }

    public function findOneById(int $id): ?UserObject
    {
        $user = User::query()->where('id', $id)->first();

        return $user ? $this->makeObjectByModel($user) : null;
    }

    public function isExistByEmail(string $email): bool
    {
        return User::query()->where('email', $email)->exists();
    }

    public function updateName(int $id, string $firstName, ?string $lastName): void
    {
        User::query()
            ->where('id', $id)
            ->update([
                'first_name' => $firstName,
                'last_name'  => $lastName,
            ]);
    }

    public function findById(int $id): ?UserDetailObject
    {
        return $this->makeUserFullObjectByUserOrNull(
            User::query()->where('id', $id)->first()
        );
    }

    public function findByEmail(string $email): ?UserDetailObject
    {
        return $this->makeUserFullObjectByUserOrNull(
            User::query()->where('email', $email)->first()
        );
    }

    private function makeUserFullObjectByUserOrNull(?User $user): ?UserDetailObject
    {
        if (!$user) {
            return null;
        }

        return new UserDetailObject(
            id: $user->id,
            firstName: $user->first_name,
            lastName: $user->last_name,
            email: $user->email,
            password: $user->password,
            createdAt: $user->created_at,
            updatedAt: $user->updated_at,
        );
    }

    private function makeObjectByModel(User $user): UserObject
    {
        return new UserObject(
            id: $user->id,
            firstName: $user->first_name,
            lastName: $user->last_name,
            email: $user->email,
            createdAt: $user->created_at,
            updatedAt: $user->updated_at,
        );
    }
}
