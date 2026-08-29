<?php

namespace Tests\Packages\Sconcur\Queue\Rabbitmq;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SConcur\Features\Amqp\Channel;
use SConcur\Features\Amqp\Connection;
use SConcur\Features\Amqp\Message;
use SConcur\Laravel\Queue\Rabbitmq\Queue;
use Throwable;

/**
 * A delay has to be one the topology serves: RetryTopology declares one wait queue per
 * delay, so a delay nothing declared addresses a queue that does not exist. Rounding up
 * is what keeps later() and release() usable with an arbitrary delay.
 */
class QueueTest extends TestCase
{
    public function testADelayIsRoundedUpToTheNearestDeclaredOne(): void
    {
        $queue = $this->queue([1000, 5000, 30000]);

        $queue->laterRaw(1, '{"id":"1"}', 'some-queue');

        $this->assertSame(1000, $queue->publishedDelayMs);
    }

    public function testADelayBetweenTwoRungsTakesTheHigherOne(): void
    {
        $queue = $this->queue([1000, 5000, 30000]);

        $queue->laterRaw(3, '{"id":"1"}', 'some-queue');

        $this->assertSame(5000, $queue->publishedDelayMs);
    }

    public function testADelayThatMatchesARungExactlyTakesIt(): void
    {
        $queue = $this->queue([1000, 5000, 30000]);

        $queue->laterRaw(5, '{"id":"1"}', 'some-queue');

        $this->assertSame(5000, $queue->publishedDelayMs);
    }

    /**
     * Past the top of the ladder the longest declared delay is the closest thing to what
     * was asked for. The alternative — passing the delay through — would address a wait
     * queue nobody declared and lose the job.
     */
    public function testADelayPastTheTopOfTheLadderTakesTheLongestRung(): void
    {
        $queue = $this->queue([1000, 5000, 30000]);

        $queue->laterRaw(120, '{"id":"1"}', 'some-queue');

        $this->assertSame(30000, $queue->publishedDelayMs);
    }

    public function testNoDelayPublishesStraightIntoTheQueue(): void
    {
        $queue = $this->queue([1000, 5000]);

        $queue->laterRaw(0, '{"id":"1"}', 'some-queue');

        $this->assertSame(0, $queue->publishedDelayMs);
    }

    public function testAnUnorderedLadderStillRoundsUpCorrectly(): void
    {
        $queue = $this->queue([30000, 1000, 5000]);

        $queue->laterRaw(2, '{"id":"1"}', 'some-queue');

        $this->assertSame(5000, $queue->publishedDelayMs);
    }

    public function testTheAttemptCounterTravelsWithAReleasedJob(): void
    {
        $queue = $this->queue([1000]);

        $queue->laterRaw(1, '{"id":"1"}', 'some-queue', attempts: 3);

        $this->assertSame(3, $queue->publishedAttempts);
    }

    public function testTheCorrelationIdIsThePayloadId(): void
    {
        $queue = $this->queue([]);

        $this->assertSame('job-7', $queue->pushRaw('{"id":"job-7"}', 'some-queue'));
    }

    public function testAPayloadWithoutAnIdHasNoCorrelationId(): void
    {
        $queue = $this->queue([]);

        $this->assertNull($queue->pushRaw('{"data":[]}', 'some-queue'));
    }

    /**
     * A queue instance is shared by every coroutine in the process, so a publish may not
     * run on a channel a property holds: channel commands are serialized, and publisher
     * confirms are channel-wide, so neighbours would read each other's answers.
     */
    public function testAPublishRunsOnALeasedChannelAndGivesItBack(): void
    {
        $queue = $this->channelRecordingQueue();

        $queue->pushRaw('{"id":"1"}', 'some-queue');

        $this->assertSame(1, $queue->leases, 'the publish leased a channel of its own');
        $this->assertSame([$queue->lent], $queue->returned, 'and gave that same one back');
        $this->assertSame($queue->lent, $queue->publishedOnChannel);
    }

    public function testTheChannelGoesBackEvenWhenThePublishThrows(): void
    {
        $queue = $this->channelRecordingQueue();

        $queue->publishThrows = new RuntimeException('broker said no');

        try {
            $queue->pushRaw('{"id":"1"}', 'some-queue');

            $this->fail('the failure should have been rethrown');
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame([$queue->lent], $queue->returned, 'a failed publish still returns its channel');
    }

    /**
     * Inside a message handler the runtime has already lent a channel nobody else holds.
     * Leasing a second one would be waste; publishing on the shared one would be the bug.
     */
    public function testAChannelHandedInIsUsedAndNotLeasedOrReturned(): void
    {
        $queue = $this->channelRecordingQueue();

        $given = new Channel(new Connection('amqp://guest:guest@127.0.0.1:5672/%2f'), 'lent-by-the-runtime', 7);

        $queue->publish(queue: 'some-queue', payload: '{"id":"1"}', attempts: 1, delayMs: 0, channel: $given);

        $this->assertSame(0, $queue->leases, 'nothing was leased');
        $this->assertSame([], $queue->returned, 'and nothing was handed to the pool');
        $this->assertSame($given, $queue->publishedOnChannel);
    }

    /**
     * Records which channel a publish ran on, with the pool and the broker both stubbed
     * out. The connection is lazy, so constructing one opens no socket.
     */
    private function channelRecordingQueue(): Queue
    {
        $connection = new Connection('amqp://guest:guest@127.0.0.1:5672/%2f');

        return new class($connection, 'default', []) extends Queue {
            public int $leases = 0;

            public ?Channel $lent = null;

            /** @var list<Channel> */
            public array $returned = [];

            public ?Channel $publishedOnChannel = null;

            public ?Throwable $publishThrows = null;

            protected function lendChannel(): Channel
            {
                $this->leases++;

                return $this->lent ??= new Channel($this->connection, 'leased-from-the-pool', 1);
            }

            protected function returnChannel(Channel $channel): void
            {
                $this->returned[] = $channel;
            }

            protected function publishOn(Channel $channel, string $queue, Message $message, int $delayMs): null
            {
                $this->publishedOnChannel = $channel;

                if ($this->publishThrows !== null) {
                    throw $this->publishThrows;
                }

                return null;
            }
        };
    }

    /**
     * @param list<int> $delaysMs
     */
    private function queue(array $delaysMs): Queue
    {
        // The connection is lazy — the constructor touches nothing — so this opens no
        // socket, and publish() is intercepted below before anything would.
        return new class(new Connection('amqp://guest:guest@127.0.0.1:5672/%2f'), 'default', $delaysMs) extends Queue {
            public ?int $publishedDelayMs = null;

            public ?int $publishedAttempts = null;

            public ?Channel $publishedOn = null;

            public function publish(
                string $queue,
                string $payload,
                int $attempts,
                int $delayMs,
                ?Channel $channel = null,
            ): void {
                $this->publishedDelayMs  = $delayMs;
                $this->publishedAttempts = $attempts;
                $this->publishedOn       = $channel;
            }
        };
    }
}
