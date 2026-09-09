<?php

namespace Tests\Modules\Watcher\Domain\Services;

use App\Modules\Watcher\Domain\Services\WatcherCollectionStart;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class WatcherCollectionStartTest extends TestCase
{
    public function testCollectionStartsAfterTheReceiverHasReadTheTableAgain(): void
    {
        $now = Carbon::parse('2026-09-08 23:43:18');

        $this->assertSame(
            '2026-09-08 23:43:48',
            new WatcherCollectionStart()->afterNextReload($now)->toDateTimeString()
        );
    }

    public function testTheMomentHandedInIsNotChanged(): void
    {
        $now = Carbon::parse('2026-09-08 23:43:18');

        new WatcherCollectionStart()->afterNextReload($now);

        $this->assertSame('2026-09-08 23:43:18', $now->toDateTimeString());
    }
}
