<?php

namespace Tests\Modules\User\Domain\Actions;

use App\Modules\User\Domain\Actions\FindUserByTokenAction;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Repositories\UserTokenRepository;
use PHPUnit\Framework\TestCase;

class FindUserByTokenActionTest extends TestCase
{
    public function testAnUnknownTokenBelongsToNobody(): void
    {
        $users  = $this->createMock(UserRepository::class);
        $tokens = $this->createMock(UserTokenRepository::class);

        // Signed out, or left unused past the idle window — the repository answers null
        // for both, and neither is a reason to go looking for a user.
        $tokens->method('findUserIdByToken')->willReturn(null);

        $users->expects($this->never())->method('findById');

        $this->assertNull(new FindUserByTokenAction($users, $tokens)->handle('gone'));
    }
}
