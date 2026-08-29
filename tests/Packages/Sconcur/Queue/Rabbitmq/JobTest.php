<?php

namespace Tests\Packages\Sconcur\Queue\Rabbitmq;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use SConcur\Features\Amqp\Delivery;
use SConcur\Features\Amqp\MessageProperties;
use SConcur\Laravel\Queue\Rabbitmq\Job;
use SConcur\Laravel\Queue\Rabbitmq\Queue;
use WeakReference;

/**
 * The attempt counter is the load-bearing detail of interoperability with
 * vladimir-yuldashev/laravel-queue-rabbitmq: it keeps the count in the `laravel.attempts`
 * header, and Worker::process() builds maxTries and the failed_jobs write on top of it.
 * Reading it from anywhere else would break retries without breaking anything visible.
 */
class JobTest extends TestCase
{
    public function testAJobThatNeverWentRoundIsOnItsFirstAttempt(): void
    {
        $job = $this->job(headers: []);

        $this->assertSame(1, $job->attempts());
    }

    public function testTheAttemptCounterComesFromTheLaravelHeader(): void
    {
        $job = $this->job(headers: [Queue::ATTEMPTS_HEADER => ['attempts' => 2]]);

        $this->assertSame(3, $job->attempts());
    }

    public function testAHeaderWithoutTheAttemptsKeyCountsAsNone(): void
    {
        $job = $this->job(headers: [Queue::ATTEMPTS_HEADER => ['something-else' => 7]]);

        $this->assertSame(1, $job->attempts());
    }

    public function testAHeaderThatIsNotATableIsIgnored(): void
    {
        $job = $this->job(headers: [Queue::ATTEMPTS_HEADER => 'not-a-table']);

        $this->assertSame(1, $job->attempts());
    }

    public function testTheJobIdIsThePayloadId(): void
    {
        $job = $this->job(headers: [], body: '{"id":"job-uuid-1","data":[]}');

        $this->assertSame('job-uuid-1', $job->getJobId());
    }

    public function testAPayloadWithoutAnIdHasNoJobId(): void
    {
        $job = $this->job(headers: [], body: '{"data":[]}');

        $this->assertNull($job->getJobId());
    }

    public function testTheQueueIsTheOneItWasConstructedWith(): void
    {
        $this->assertSame('some-queue', $this->job(headers: [])->getQueue());
    }

    /**
     * @param array<string, mixed> $headers
     */
    private function job(array $headers, string $body = '{"id":"job-1","data":[]}'): Job
    {
        $channel = new \stdClass();

        return new Job(
            container: new Container(),
            rabbitmq: $this->createMock(Queue::class),
            delivery: new Delivery(
                body: $body,
                routingKey: 'some-queue',
                exchange: '',
                consumerTag: 'tag',
                deliveryTag: 1,
                redelivered: false,
                properties: new MessageProperties(headers: $headers),
                channel: WeakReference::create($channel),
            ),
            connectionName: 'sconcur_rabbitmq',
            queue: 'some-queue',
        );
    }
}
