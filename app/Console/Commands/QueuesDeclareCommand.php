<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use VladimirYuldashev\LaravelQueueRabbitMQ\Console\QueueDeclareCommand;

class QueuesDeclareCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queues-declare';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for queue declaration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->declareRabbitmq();

        return self::SUCCESS;
    }

    private function declareRabbitmq(): void
    {
        $this->warn('[RABBITMQ]');

        $rabbitmqQueueNames = [];

        if (config('queue.queues.creating.connection') === 'rabbitmq') {
            $rabbitmqQueueNames[] = config('queue.queues.creating.name');
        }

        if (config('slogger.dispatchers.default') === 'queue') {
            if (config('slogger.dispatchers.queue.connection') === 'rabbitmq') {
                $rabbitmqQueueNames[] = config('slogger.dispatchers.queue.name');
            }
        }

        if (config('slogger.dispatchers.default') === 'transporter') {
            if (config('slogger.dispatchers.transporter.queue.connection') === 'rabbitmq') {
                $rabbitmqQueueNames[] = config('slogger.dispatchers.transporter.queue.name');
            }
        }

        if (empty($rabbitmqQueueNames)) {
            $this->error('No queues found.');

            return;
        }

        foreach (array_unique($rabbitmqQueueNames) as $rabbitmqQueueName) {
            $this->info("Creating [$rabbitmqQueueName]...");

            $this->call(QueueDeclareCommand::class, [
                'name' => $rabbitmqQueueName,
            ]);
        }
    }
}
