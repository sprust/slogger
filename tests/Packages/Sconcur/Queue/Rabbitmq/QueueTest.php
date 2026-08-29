<?php

namespace Tests\Packages\Sconcur\Queue\Rabbitmq;

use PHPUnit\Framework\TestCase;
use SConcur\Features\Amqp\Connection;
use SConcur\Laravel\Queue\Rabbitmq\Queue;

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
     * @param list<int> $delaysMs
     */
    private function queue(array $delaysMs): Queue
    {
        // The connection is lazy — the constructor touches nothing — so this opens no
        // socket, and publish() is intercepted below before anything would.
        return new class(new Connection('amqp://guest:guest@127.0.0.1:5672/%2f'), 'default', $delaysMs) extends Queue {
            public ?int $publishedDelayMs = null;

            public ?int $publishedAttempts = null;

            public function publish(string $queue, string $payload, int $attempts, int $delayMs): void
            {
                $this->publishedDelayMs  = $delayMs;
                $this->publishedAttempts = $attempts;
            }
        };
    }
}
