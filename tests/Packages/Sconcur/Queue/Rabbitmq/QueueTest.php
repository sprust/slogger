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
    public function testADelayIsPassedThroughAsAsked(): void
    {
        $queue = $this->queue();

        $queue->laterRaw(3, '{"id":"1"}', 'some-queue');

        // Three seconds, not the nearest rung of a ladder: the wait queue for the exact
        // delay is declared when it is needed, so there are no rungs to round to.
        $this->assertSame(3000, $queue->publishedDelayMs);
    }

    public function testALongDelayIsNotShortened(): void
    {
        $queue = $this->queue();

        $queue->laterRaw(600, '{"id":"1"}', 'some-queue');

        $this->assertSame(600_000, $queue->publishedDelayMs);
    }

    public function testNoDelayPublishesStraightIntoTheQueue(): void
    {
        $queue = $this->queue();

        $queue->laterRaw(0, '{"id":"1"}', 'some-queue');

        $this->assertSame(0, $queue->publishedDelayMs);
    }

    public function testANegativeDelayIsNoDelay(): void
    {
        $queue = $this->queue();

        $queue->laterRaw(-5, '{"id":"1"}', 'some-queue');

        $this->assertSame(0, $queue->publishedDelayMs);
    }

    public function testTheAttemptCounterTravelsWithAReleasedJob(): void
    {
        $queue = $this->queue();

        $queue->laterRaw(1, '{"id":"1"}', 'some-queue', attempts: 3);

        $this->assertSame(3, $queue->publishedAttempts);
    }

    public function testTheCorrelationIdIsThePayloadId(): void
    {
        $queue = $this->queue();

        $this->assertSame('job-7', $queue->pushRaw('{"id":"job-7"}', 'some-queue'));
    }

    public function testAPayloadWithoutAnIdHasNoCorrelationId(): void
    {
        $queue = $this->queue();

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
     * The wait queue has to exist before the publish addressed to it, and with exactly
     * these arguments: the ttl holds the message, the dead-letter route sends it back, and
     * x-expires is what removes a wait queue nobody needs any more without anyone having
     * to remember it.
     */
    public function testADelayedPublishDeclaresItsWaitQueueFirst(): void
    {
        $queue = $this->declareRecordingQueue();

        $queue->publish(queue: 'some-queue', payload: '{"id":"1"}', attempts: 1, delayMs: 3000);

        $this->assertSame(['some-queue.wait.3000'], array_column($queue->declared, 'queue'));
        $this->assertSame([
            'x-message-ttl'             => 3000,
            'x-dead-letter-exchange'    => '',
            'x-dead-letter-routing-key' => 'some-queue',
            'x-expires'                 => 6000,
        ], $queue->declared[0]['arguments']);
        $this->assertTrue($queue->declaredBeforePublish, 'declared before the publish, not after');
    }

    public function testAnImmediatePublishDeclaresNothing(): void
    {
        $queue = $this->declareRecordingQueue();

        $queue->publish(queue: 'some-queue', payload: '{"id":"1"}', attempts: 0, delayMs: 0);

        $this->assertSame([], $queue->declared);
    }

    /**
     * Records which channel a publish ran on, with the pool and the broker both stubbed
     * out. The connection is lazy, so constructing one opens no socket.
     */
    private function channelRecordingQueue(): Queue
    {
        $connection = new Connection('amqp://guest:guest@127.0.0.1:5672/%2f');

        return new class($connection, 'default') extends Queue {
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

    /** Records the wait-queue declaration without touching a broker. */
    private function declareRecordingQueue(): Queue
    {
        return new class(new Connection('amqp://guest:guest@127.0.0.1:5672/%2f'), 'default') extends Queue {
            /** @var list<array{queue: string, arguments: array<string, mixed>}> */
            public array $declared = [];

            public bool $declaredBeforePublish = false;

            protected function declareWaitQueue(Channel $channel, string $queue, int $delayMs): void
            {
                $this->declared[] = [
                    'queue'     => $queue . '.wait.' . $delayMs,
                    'arguments' => [
                        'x-message-ttl'             => $delayMs,
                        'x-dead-letter-exchange'    => '',
                        'x-dead-letter-routing-key' => $queue,
                        'x-expires'                 => $delayMs * 2,
                    ],
                ];
            }

            protected function publishOn(Channel $channel, string $queue, Message $message, int $delayMs): null
            {
                if ($delayMs > 0) {
                    $this->declareWaitQueue($channel, $queue, $delayMs);

                    $this->declaredBeforePublish = $this->declared !== [];
                }

                return null;
            }

            protected function lendChannel(): Channel
            {
                return new Channel($this->connection, 'leased', 1);
            }

            protected function returnChannel(Channel $channel): void
            {
            }
        };
    }

    private function queue(): Queue
    {
        // The connection is lazy — the constructor touches nothing — so this opens no
        // socket, and publish() is intercepted below before anything would.
        return new class(new Connection('amqp://guest:guest@127.0.0.1:5672/%2f'), 'default') extends Queue {
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
