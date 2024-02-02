<?php

namespace App\Modules\Auth\Adapters;

use App\Models\Users\User;
use App\Modules\Users\Repository\UsersRepository;
use Illuminate\Contracts\Foundation\Application;

readonly class AuthUsersAdapter
{
    public function __construct(
        private Application $app
    ) {
    }

    public function findUserByEmail(string $email): ?User
    {
        return $this->app->make(UsersRepository::class)->findByEmail($email);
    }
}
