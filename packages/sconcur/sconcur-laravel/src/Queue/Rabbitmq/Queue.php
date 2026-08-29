<?php

declare(strict_types=1);

namespace SConcur\Laravel\Queue\Rabbitmq;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue as BaseQueue;
use SConcur\Features\Amqp\Channel;
use SConcur\Features\Amqp\Connection;
use SConcur\Features\Amqp\Message;
use SConcur\Features\Amqp\Queue as AmqpQueue;

/**
 * A Laravel queue over the SConcur AMQP feature.
 *
 * The wire format is deliberately not ours: body, message properties and the attempt
 * header are exactly what vladimir-yuldashev/laravel-queue-rabbitmq writes, so a job
 * published by either side is readable and runnable by the other. See the
 * compatibility table in .ai/plans/sconcur-amqp-laravel-queue.md.
 *
 * Publishing goes to the default exchange with the queue name as the routing key —
 * straight into the queue — which is what that package does with its own default
 * configuration.
 */
class Queue extends BaseQueue implements QueueContract
{
    /** The header the attempt counter lives in, nested as `laravel.attempts`. */
    public const string ATTEMPTS_HEADER = 'laravel';

    protected ?Channel $sharedChannel = null;

    /**
     * @param list<int> $delaysMs the declared wait-queue delays a later() may address
     */
    public function __construct(
        protected Connection $connection,
        protected string $default = 'default',
        protected array $delaysMs = [],
        protected bool $confirmPublishes = false,
        protected float $confirmTimeoutSeconds = 5.0,
    ) {
    }

    public function size($queue = null): int
    {
        return $this->amqpQueue($this->getQueue($queue))->declarePassive()->messageCount;
    }

    public function push($job, $data = '', $queue = null): mixed
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $this->getQueue($queue), $data),
            $queue,
            null,
            fn(string $payload, ?string $queue): mixed => $this->pushRaw($payload, $queue),
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    public function pushRaw($payload, $queue = null, array $options = []): mixed
    {
        $attempts = (int) ($options['attempts'] ?? 0);

        $this->publish(
            queue: $this->getQueue($queue),
            payload: $payload,
            attempts: $attempts,
            delayMs: 0,
        );

        return $this->correlationId($payload);
    }

    public function later($delay, $job, $data = '', $queue = null): mixed
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $this->getQueue($queue), $data, $delay),
            $queue,
            $delay,
            fn(string $payload, ?string $queue, mixed $delay): mixed => $this->laterRaw($delay, $payload, $queue),
        );
    }

    /**
     * The delay is rounded up to the nearest declared wait queue, because a delay must
     * be one the topology serves: RetryTopology declares one queue per delay rather
     * than one queue with a per-message TTL, since a classic queue expires only from
     * its head.
     */
    public function laterRaw(mixed $delay, string $payload, ?string $queue = null, int $attempts = 0): mixed
    {
        $this->publish(
            queue: $this->getQueue($queue),
            payload: $payload,
            attempts: $attempts,
            delayMs: $this->delayMsFor($delay),
        );

        return $this->correlationId($payload);
    }

    /**
     * @param iterable<mixed> $jobs
     */
    public function bulk($jobs, $data = '', $queue = null): void
    {
        foreach ($jobs as $job) {
            $this->push($job, $data, $queue);
        }
    }

    /**
     * An honest basic.get. It is what makes queue:size and a plain queue:work usable in
     * development; the concurrency win is on the consumer pool, not here.
     */
    public function pop($queue = null): ?Job
    {
        $name = $this->getQueue($queue);

        $delivery = $this->amqpQueue($name)->get();

        if ($delivery === null) {
            return null;
        }

        return new Job(
            container: $this->container,
            rabbitmq: $this,
            delivery: $delivery,
            connectionName: $this->connectionName,
            queue: $name,
        );
    }

    /**
     * Publish a raw payload, keeping the attempt counter where the other package's
     * consumer reads it.
     */
    public function publish(string $queue, string $payload, int $attempts, int $delayMs): void
    {
        $message = new Message(
            body: $payload,
            contentType: 'application/json',
            persistent: true,
            correlationId: $this->correlationId($payload),
            headers: [self::ATTEMPTS_HEADER => ['attempts' => $attempts]],
        );

        $amqpQueue = $this->amqpQueue($queue);

        // A delayed publish is always confirmed, whatever the connection asked for.
        // It addresses a wait queue rather than the queue itself, and a wait queue is
        // the easy one to forget to declare: an unconfirmed publish to a routing key
        // nothing is bound to is dropped by the broker without a word, so a job whose
        // handler released it would disappear. Confirmed publishing is mandatory by
        // default, so the same case throws UnroutableMessageException instead.
        if ($delayMs > 0 || $this->confirmPublishes) {
            $amqpQueue->publishConfirmed(
                message: $message,
                timeoutSeconds: $this->confirmTimeoutSeconds,
                delayMs: $delayMs,
            );

            return;
        }

        $amqpQueue->publish($message);
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return list<int>
     */
    public function delaysMs(): array
    {
        return $this->delaysMs;
    }

    public function getQueue(mixed $queue = null): string
    {
        return (string) ($queue ?: $this->default);
    }

    protected function amqpQueue(string $name): AmqpQueue
    {
        return $this->channel()->queue($name);
    }

    /**
     * One channel for this queue instance: a channel is a handle, and opening one per
     * publish would cost a round trip per message for nothing.
     */
    protected function channel(): Channel
    {
        if ($this->sharedChannel === null || !$this->sharedChannel->isOpen()) {
            $this->sharedChannel = $this->connection->channel();
        }

        return $this->sharedChannel;
    }

    /**
     * The nearest declared delay at or above the requested one. Without a ladder the
     * delay is passed through, which addresses a wait queue that must already exist.
     */
    protected function delayMsFor(mixed $delay): int
    {
        $requestedMs = $this->secondsUntil($delay) * 1000;

        if ($requestedMs <= 0) {
            return 0;
        }

        $candidates = $this->delaysMs;

        sort($candidates);

        foreach ($candidates as $declared) {
            if ($declared >= $requestedMs) {
                return $declared;
            }
        }

        // Past the top of the ladder the longest declared delay is the closest thing to
        // what was asked for; the alternative is losing the job to an unroutable delay.
        return $candidates === [] ? $requestedMs : (int) end($candidates);
    }

    protected function correlationId(string $payload): ?string
    {
        $decoded = json_decode($payload, true);

        if (!is_array($decoded)) {
            return null;
        }

        $id = $decoded['id'] ?? null;

        return is_string($id) ? $id : null;
    }
}
