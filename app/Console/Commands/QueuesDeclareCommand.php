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

        $rabbitmqQueueNames = [
            config('queue.queues.creating.name')
        ];

        if (config('slogger.queue.connection') === 'rabbitmq') {
            $rabbitmqQueueNames[] = config('slogger.queue.name');
        }
        if (config('slogger.queue_transporter.connection') === 'rabbitmq') {
            $rabbitmqQueueNames[] = config('slogger.queue_transporter.name');
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
