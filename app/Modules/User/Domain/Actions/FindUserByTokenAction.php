<?php

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Domain\Actions\Interfaces\FindUserByTokenActionInterface;
use App\Modules\User\Domain\Entities\Objects\UserDetailObject;
use App\Modules\User\Domain\Entities\Transports\UserDetailTransport;
use App\Modules\User\Repositories\UserRepositoryInterface;

readonly class FindUserByTokenAction implements FindUserByTokenActionInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function handle(string $token): ?UserDetailObject
    {
        $userDto = $this->userRepository->findByToken(token: $token);

        if (!$userDto) {
            return null;
        }

        return UserDetailTransport::toObject($userDto);
    }
}
