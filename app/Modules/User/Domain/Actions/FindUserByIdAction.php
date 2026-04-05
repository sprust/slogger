<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Entities\UserDetailObject;
use App\Modules\User\Repositories\UserRepository;

readonly class FindUserByIdAction
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function handle(int $id): ?UserDetailObject
    {
        return $this->userRepository->findById(id: $id);
    }
}
