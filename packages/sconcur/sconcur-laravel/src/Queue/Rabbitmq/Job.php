<?php

declare(strict_types=1);

namespace SConcur\Laravel\Queue\Rabbitmq;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job as BaseJob;
use SConcur\Features\Amqp\Delivery;

/**
 * One AMQP delivery as a Laravel job, which is what Illuminate\Queue\Worker::process()
 * takes. It is the whole adapter: the worker already raises the job events, honours
 * maxTries and backoff and writes failed_jobs, and none of that is reimplemented here.
 *
 * The attempt counter is read from, and written back to, the `laravel.attempts` header
 * — the same place vladimir-yuldashev/laravel-queue-rabbitmq keeps it. That is what
 * makes a job published by that package retry correctly here, and vice versa; x-death
 * is not it, and counting attempts anywhere else would silently break maxTries.
 */
class Job extends BaseJob implements JobContract
{
    public function __construct(
        Container $container,
        protected Queue $rabbitmq,
        protected Delivery $delivery,
        string $connectionName,
        string $queue,
    ) {
        $this->container      = $container;
        $this->connectionName = $connectionName;
        $this->queue          = $queue;
    }

    public function getRawBody(): string
    {
        return $this->delivery->body;
    }

    public function getJobId(): ?string
    {
        $id = $this->payload()['id'] ?? null;

        return is_string($id) ? $id : null;
    }

    public function attempts(): int
    {
        return $this->attemptsBefore() + 1;
    }

    /**
     * Acknowledge: the broker may forget the message.
     *
     * The delivery refuses to be settled twice, so once this has run the consumer
     * runtime leaves the message alone — it does not override a handler that settled
     * its own delivery.
     */
    public function delete(): void
    {
        parent::delete();

        if (!$this->delivery->isSettled()) {
            $this->delivery->ack();
        }
    }

    /**
     * Put the job back, with its attempt counter advanced.
     *
     * Republishing rather than nack(requeue: true), because the counter has to move: a
     * requeued delivery comes back with the header it arrived with, and a job that
     * always fails would then retry forever with attempts stuck at one. The seam this
     * creates — publish, then ack — is the ordinary at-least-once one: a worker dying
     * between them leaves the copy and the original, which is why handlers must be
     * idempotent.
     */
    public function release($delay = 0): void
    {
        parent::release($delay);

        $this->rabbitmq->laterRaw(
            delay: $delay,
            payload: $this->getRawBody(),
            queue: $this->queue,
            attempts: $this->attempts(),
        );

        if (!$this->delivery->isSettled()) {
            $this->delivery->ack();
        }
    }

    public function getDelivery(): Delivery
    {
        return $this->delivery;
    }

    /**
     * What the header says before this attempt is counted. A message that never went
     * round carries no header at all, which is zero.
     */
    protected function attemptsBefore(): int
    {
        $header = $this->delivery->header(Queue::ATTEMPTS_HEADER);

        if (!is_array($header)) {
            return 0;
        }

        return (int) ($header['attempts'] ?? 0);
    }
}
