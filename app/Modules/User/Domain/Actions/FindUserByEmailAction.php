<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Contracts\Domain\FindUserByEmailActionInterface;
use App\Modules\User\Contracts\Repositories\UserRepositoryInterface;
use App\Modules\User\Entities\UserDetailObject;

readonly class FindUserByEmailAction implements FindUserByEmailActionInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function handle(string $email): ?UserDetailObject
    {
        return $this->userRepository->findByEmail(email: $email);
    }
}
