<?php

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Domain\Actions\Interfaces\FindUserByEmailActionInterface;
use App\Modules\User\Domain\Entities\Objects\UserDetailObject;
use App\Modules\User\Domain\Entities\Transports\UserDetailTransport;
use App\Modules\User\Repositories\UserRepositoryInterface;

readonly class FindUserByEmailAction implements FindUserByEmailActionInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function handle(string $email): ?UserDetailObject
    {
        $userDto = $this->userRepository->findByEmail(email: $email);

        if (!$userDto) {
            return null;
        }

        return UserDetailTransport::toObject($userDto);
    }
}
