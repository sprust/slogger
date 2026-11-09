<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Services;

use App\Modules\Common\Infrastructure\Services\Objects\QueueRouteObject;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Queue\ShouldQueue;

readonly class QueueRoutePolicy
{
    public function __construct(private Repository $configRepository)
    {
    }

    public function resolve(ShouldQueue $job): QueueRouteObject
    {
        $connection = $job->connection ?? $this->configRepository->get(
            key: 'queue.default',
        );
        $connectionName = is_string($connection) ? $connection : '';
        $driver         = $this->configRepository->get(
            key: "queue.connections.{$connectionName}.driver",
        );
        $queue = $job->queue ?? $this->configRepository->get(
            key: "queue.connections.{$connectionName}.queue",
        ) ?? 'default';
        $queueName          = is_string($queue) ? $queue : '';
        $rabbitmqConnection = $this->configRepository->get(
            key: 'sconcur.queue.rabbitmq.connection',
        );
        $rabbitmqQueues = $this->configRepository->get(
            key: 'sconcur.queue.rabbitmq.queues',
            default: [],
        );
        $allowed = $connectionName !== '' && $queueName !== '' && ($driver === 'sync' || $driver === 'sconcur_rabbitmq' && $connectionName === $rabbitmqConnection && is_array($rabbitmqQueues) && in_array(
            $queueName,
            $rabbitmqQueues,
            true,
        ));

        return new QueueRouteObject(
            connection: $connectionName,
            queue: $queueName,
            allowed: $allowed,
        );
    }
}
