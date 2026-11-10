<?php

declare(strict_types=1);

namespace Tests\Factories\Entities;

use App\Modules\User\Repositories\UserTokenRepository;
use Illuminate\Support\Carbon;

readonly class UserTokenFactory
{
    public function __construct(
        private UserTokenRepository $userTokenRepository,
        private UserFactory $userFactory
    ) {
    }

    public function create(
        ?int $userId = null,
        ?string $token = null,
        ?Carbon $createdAt = null,
        ?Carbon $expiresAt = null,
    ): string {
        $token     = $token ?? fake()->uuid();
        $createdAt = $createdAt ?? Carbon::now();

        $this->userTokenRepository->create(
            userId: $userId ?? $this->userFactory->create(),
            token: $token,
            now: $createdAt,
            expiresAt: $expiresAt ?? $createdAt->copy()->addDay(),
        );

        return $token;
    }
}
