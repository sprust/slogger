<?php

namespace Tests\Packages\Sconcur\Tasks;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;
use SConcur\Laravel\Tasks\Control\ControlActionEnum;
use SConcur\Laravel\Tasks\Control\ControlChannel;
use SConcur\Laravel\Tasks\Control\ControlCommandDto;

/**
 * The channel is what makes a stop survivable. A command left in the cache after the
 * pool acted on it would be read again by the process the supervisor starts in its
 * place, and the pool would stop for ever, one restart at a time.
 */
class ControlChannelTest extends TestCase
{
    public function testACommandPostedAfterTheGivenMomentIsTaken(): void
    {
        $channel = $this->channel();

        $sent = $channel->send(ControlActionEnum::Stop);

        // Anchored to the command rather than to the clock: two microtime() calls in a
        // row can return the same value, and "newer than the pool's start" is a strict
        // comparison.
        $commands = $channel->takeAll($sent->at - 0.001);

        $this->assertCount(1, $commands);
        $this->assertSame(ControlActionEnum::Stop, $commands[0]->action);
        $this->assertTrue($commands[0]->targetsAll());
    }

    public function testACommandOlderThanTheGivenMomentIsIgnored(): void
    {
        $channel = $this->channel();

        $sent = $channel->send(ControlActionEnum::Stop);

        // Everything posted before the pool started belongs to the pool that came
        // before it — including the stop that ended that one.
        $this->assertSame([], $channel->takeAll($sent->at + 0.001));
    }

    public function testTakingClearsTheKeySoOneCommandActsOnce(): void
    {
        $channel = $this->channel();

        $sent = $channel->send(ControlActionEnum::Restart, 'cron');

        $this->assertCount(1, $channel->takeAll($sent->at - 0.001));
        $this->assertSame([], $channel->takeAll($sent->at - 0.001));
    }

    public function testAStaleCommandIsClearedToo(): void
    {
        $cache   = new Repository(new ArrayStore());
        $channel = new ControlChannel($cache, 'tasks:control');

        $sent = $channel->send(ControlActionEnum::Stop);
        $channel->takeAll($sent->at + 0.001);

        $this->assertNull($cache->get('tasks:control'));
    }

    public function testAnUnreadableCommandIsNotTaken(): void
    {
        $cache = new Repository(new ArrayStore());
        $cache->forever('tasks:control', ['action' => 'explode', 'target' => '*', 'at' => microtime(true)]);

        // Written the way an older build wrote it — one command, not a list — so this
        // also covers a pool reading a key left by the version before it.
        $this->assertSame([], new ControlChannel($cache, 'tasks:control')->takeAll(0));
    }

    /**
     * The pool reads the key on its poll interval, so two commands sent inside one of
     * those windows arrive together. Overwriting would drop the first without a trace.
     */
    public function testTwoCommandsSentBeforeAPollAreBothDelivered(): void
    {
        $channel = $this->channel();

        $first = $channel->send(ControlActionEnum::Stop, 'cron');
        $channel->send(ControlActionEnum::Stop, 'trace-dynamic-indexes');

        $commands = $channel->takeAll($first->at - 0.001);

        $this->assertCount(2, $commands);
        $this->assertSame('cron', $commands[0]->target);
        $this->assertSame('trace-dynamic-indexes', $commands[1]->target);
    }

    public function testATargetedCommandOnlyTargetsThatTask(): void
    {
        $command = new ControlCommandDto(ControlActionEnum::Stop, 'cron', microtime(true));

        $this->assertTrue($command->targets('cron'));
        $this->assertFalse($command->targets('trace-dynamic-indexes'));
    }

    private function channel(): ControlChannel
    {
        return new ControlChannel(new Repository(new ArrayStore()), 'tasks:control');
    }
}
