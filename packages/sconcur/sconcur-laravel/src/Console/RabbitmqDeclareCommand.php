<?php

declare(strict_types=1);

namespace SConcur\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Queue\QueueManager;
use SConcur\Features\Amqp\RetryTopology;
use SConcur\Laravel\Queue\Rabbitmq\Queue;

/**
 * Declare the queues the consumer pool reads, and the wait queues its delays go
 * through. Nothing else declares them: the consumer runtime declares nothing at all, so
 * a pool started before its topology exists would spin on 404.
 *
 * The flags match what vladimir-yuldashev/laravel-queue-rabbitmq declares with —
 * durable, not exclusive, not auto-delete, no arguments — because a queue re-declared
 * with different flags answers 406 and takes the channel down with it. Anything that
 * changes here has to change there too, or the two stop being able to share a queue.
 */
class RabbitmqDeclareCommand extends Command
{
    protected $signature = 'sconcur:rabbitmq:declare
        {--connection= : The config/queue.php connection to declare on}';

    protected $description = 'Declare the SConcur AMQP queues and their wait queues';

    public function handle(QueueManager $manager): int
    {
        $config = (array) config('sconcur.queue.rabbitmq', []);

        $option = $this->option('connection');

        $connectionName = is_string($option) && $option !== ''
            ? $option
            : (string) ($config['connection'] ?? 'sconcur_rabbitmq');

        $queueNames = array_values(array_unique(array_map(
            strval(...),
            (array) ($config['queues'] ?? []),
        )));

        if ($queueNames === []) {
            $this->error('No queues configured in sconcur.queue.rabbitmq.queues.');

            return self::FAILURE;
        }

        $queue = $manager->connection($connectionName);

        if (!$queue instanceof Queue) {
            $this->error(sprintf('Connection [%s] is not a SConcur AMQP queue.', $connectionName));

            return self::FAILURE;
        }

        $channel = $queue->getConnection()->channel();
        $delays  = $queue->delaysMs();

        foreach ($queueNames as $name) {
            $channel->queue($name)->declare(durable: true, exclusive: false, autoDelete: false);

            $this->info(sprintf('Declared [%s].', $name));

            if ($delays === []) {
                continue;
            }

            RetryTopology::declare(channel: $channel, queue: $name, delaysMs: $delays);

            $this->line(sprintf('  wait queues: %s', implode(', ', array_map(
                static fn(int $ms): string => $name . '.wait.' . $ms,
                $delays,
            ))));
        }

        return self::SUCCESS;
    }
}
