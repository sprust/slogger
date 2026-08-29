<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SConcur\Laravel\Console\RabbitmqDeclareCommand;
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
        $this->declareSconcurRabbitmq();
        $this->declareRabbitmq();

        return self::SUCCESS;
    }

    /**
     * The queues the sconcur consumer pool reads, and the wait queues their delays go
     * through. Nothing else declares them — the consumer runtime declares nothing — so a
     * pool started before this would spin on 404.
     */
    private function declareSconcurRabbitmq(): void
    {
        $this->warn('[SCONCUR RABBITMQ]');

        $this->call(RabbitmqDeclareCommand::class);
    }

    /**
     * The queues still served by the php-amqplib driver: the `slogger` dispatcher queue,
     * which belongs to the external slogger/laravel package, and anything else left on
     * the `rabbitmq` connection.
     */
    private function declareRabbitmq(): void
    {
        $this->warn('[RABBITMQ]');

        $rabbitmqQueueNames = [];

        if (config('slogger.dispatchers.default') === 'queue') {
            if (config('slogger.dispatchers.queue.connection') === 'rabbitmq') {
                $rabbitmqQueueNames[] = config('slogger.dispatchers.queue.name');
            }
        }

        if (config('module-trace.queue.connection') === 'rabbitmq') {
            $rabbitmqQueueNames[] = config('module-trace.queue.name');
        }

        if (empty($rabbitmqQueueNames)) {
            $this->line('No queues on the php-amqplib connection.');

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
