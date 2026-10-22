<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Entities\UserObject;
use App\Modules\User\Repositories\UserRepository;

readonly class FindUsersAction
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    /**
     * @return UserObject[]
     */
    public function handle(): array
    {
        return $this->userRepository->find();
    }
}
