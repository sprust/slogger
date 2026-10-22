<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Entities\UserObject;
use App\Modules\User\Repositories\UserRepository;

readonly class FindUserAction
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function handle(int $id): ?UserObject
    {
        return $this->userRepository->findOneById($id);
    }
}
