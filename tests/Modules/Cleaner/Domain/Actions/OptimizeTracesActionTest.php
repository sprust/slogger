<?php

declare(strict_types=1);

namespace Tests\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Domain\Actions\OptimizeTracesAction;
use App\Modules\Trace\Domain\Actions\Mutations\OptimizePartitionsAction;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class OptimizeTracesActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testMergesTheHoursThatClosedAnHourAgo(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-29 15:10:00', 'Europe/Moscow'));

        $optimizePartitions = $this->createMock(OptimizePartitionsAction::class);
        $optimizePartitions->expects($this->once())
            ->method('handle')
            ->with(
                $this->callback(
                    static fn(Carbon $to): bool => $to->format('Y-m-d H:i:s e') === '2026-09-29 11:10:00 UTC'
                )
            )
            ->willReturn(3);

        $this->assertSame(3, new OptimizeTracesAction($optimizePartitions)->handle());
    }
}
