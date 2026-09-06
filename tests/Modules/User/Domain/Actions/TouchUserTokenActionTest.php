<?php

namespace Tests\Modules\User\Domain\Actions;

use App\Modules\User\Domain\Actions\TouchUserTokenAction;
use App\Modules\User\Domain\Services\UserTokenLifetimeService;
use App\Modules\User\Repositories\UserTokenRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class TouchUserTokenActionTest extends TestCase
{
    public function testTheRenewalIsMeasuredFromTheMomentItIsStampedWith(): void
    {
        $tokens = $this->createMock(UserTokenRepository::class);

        $tokens->expects($this->once())
            ->method('touch')
            ->willReturnCallback(
                function (string $token, Carbon $now, Carbon $expiresAt): bool {
                    $this->assertSame('a-token', $token);
                    $this->assertSame(
                        UserTokenLifetimeService::IDLE_DAYS,
                        (int) $now->diffInDays($expiresAt)
                    );

                    return true;
                }
            );

        $this->assertTrue(
            new TouchUserTokenAction($tokens, new UserTokenLifetimeService())->handle('a-token')
        );
    }

    public function testASessionThatIsNoLongerThereIsReported(): void
    {
        $tokens = $this->createMock(UserTokenRepository::class);

        // Logged out during the request, or already past its window — the repository
        // refuses both, and the caller is told rather than left assuming a renewal.
        $tokens->method('touch')->willReturn(false);

        $this->assertFalse(
            new TouchUserTokenAction($tokens, new UserTokenLifetimeService())->handle('gone')
        );
    }
}
