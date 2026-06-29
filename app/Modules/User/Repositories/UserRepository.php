<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Models\Users\User;
use App\Modules\User\Entities\UserDetailObject;
use App\Modules\User\Parameters\UserCreateParameters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserRepository
{
    public function create(UserCreateParameters $parameters): int
    {
        $now = now()->toDateTimeString();

        $result = User::sconcur()->exec(
            'INSERT INTO users (first_name, last_name, email, password, api_token, created_at, updated_at)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $parameters->firstName,
                $parameters->lastName,
                $parameters->email,
                Hash::make($parameters->password),
                Str::random(50),
                $now,
                $now,
            ]
        );

        return $result->lastInsertId;
    }

    public function findById(int $id): ?UserDetailObject
    {
        return $this->findOneBy('id', $id);
    }

    public function findByEmail(string $email): ?UserDetailObject
    {
        return $this->findOneBy('email', $email);
    }

    public function findByToken(string $token): ?UserDetailObject
    {
        return $this->findOneBy('api_token', $token);
    }

    private function findOneBy(string $column, int|string $value): ?UserDetailObject
    {
        $rows = User::sconcur()->fetchAll(
            "SELECT id, first_name, last_name, email, password, api_token, created_at, updated_at"
            . " FROM users WHERE $column = ? LIMIT 1",
            [$value]
        );

        if (count($rows) === 0) {
            return null;
        }

        return $this->makeUserObjectByRow($rows[0]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function makeUserObjectByRow(array $row): UserDetailObject
    {
        return new UserDetailObject(
            id: (int) $row['id'],
            firstName: (string) $row['first_name'],
            lastName: $row['last_name'] !== null ? (string) $row['last_name'] : null,
            email: (string) $row['email'],
            password: (string) $row['password'],
            apiToken: (string) $row['api_token'],
            createdAt: new Carbon((string) $row['created_at']),
            updatedAt: new Carbon((string) $row['updated_at']),
        );
    }
}
