<?php

namespace Tests\Modules\User\Domain\Actions;

use App\Modules\User\Domain\Actions\CreateUserTokenAction;
use App\Modules\User\Domain\Services\UserTokenLifetimeService;
use App\Modules\User\Repositories\UserTokenRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class CreateUserTokenActionTest extends TestCase
{
    public function testTheTokenHandedBackIsTheOneThatWasStored(): void
    {
        $tokens = $this->createMock(UserTokenRepository::class);

        $stored = null;

        $tokens->expects($this->once())
            ->method('create')
            ->willReturnCallback(function (int $userId, string $token) use (&$stored): void {
                $stored = $token;
            });

        $returned = new CreateUserTokenAction($tokens, new UserTokenLifetimeService())->handle(7);

        // The one thing that cannot be allowed to drift: the caller signs in with what
        // this returns, and only what reached the repository will ever be recognised.
        $this->assertSame($stored, $returned);
        $this->assertNotSame('', $returned);
    }

    public function testTheSessionIsOpenedWithTheIdleWindowAhead(): void
    {
        $tokens = $this->createMock(UserTokenRepository::class);

        $tokens->expects($this->once())
            ->method('create')
            ->willReturnCallback(
                function (int $userId, string $token, Carbon $now, Carbon $expiresAt): void {
                    // The window is measured from the moment the row is stamped with, not
                    // from a second reading of the clock taken somewhere else.
                    $this->assertSame(7, $userId);
                    $this->assertSame(
                        UserTokenLifetimeService::IDLE_DAYS,
                        (int) $now->diffInDays($expiresAt)
                    );
                }
            );

        new CreateUserTokenAction($tokens, new UserTokenLifetimeService())->handle(7);
    }
}
