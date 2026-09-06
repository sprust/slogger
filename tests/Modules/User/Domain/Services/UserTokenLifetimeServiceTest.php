<?php

namespace Tests\Modules\User\Domain\Services;

use App\Modules\User\Domain\Services\UserTokenLifetimeService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class UserTokenLifetimeServiceTest extends TestCase
{
    public function testASessionLastsFifteenDaysFromItsLastUse(): void
    {
        $now = Carbon::parse('2026-09-06 10:00:00');

        $this->assertSame(
            '2026-09-21 10:00:00',
            new UserTokenLifetimeService()->expiresAt($now)->toDateTimeString()
        );
    }

    public function testTheMomentHandedInIsNotMoved(): void
    {
        $now = Carbon::parse('2026-09-06 10:00:00');

        new UserTokenLifetimeService()->expiresAt($now);

        // Carbon mutates in place. The caller writes this same instance as `last_used_at`
        // beside the expiry, and a shifted one would date the session a fortnight ahead.
        $this->assertSame('2026-09-06 10:00:00', $now->toDateTimeString());
    }
}
