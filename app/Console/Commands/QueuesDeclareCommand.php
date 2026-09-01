<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SConcur\Laravel\Console\RabbitmqDeclareCommand;

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
     * The queues the sconcur consumer pool reads, and the wait queues their delays go
     * through. Nothing else declares them — the consumer runtime declares nothing — so a
     * pool started before this would spin on 404.
     *
     * One call, because there is one transport left. The `slogger` queue used to be
     * declared separately by the php-amqplib driver, which published to it while this
     * pool read it; both ends are the sconcur driver now, and config/sconcur.php has
     * listed that queue among the pool's all along.
     */
    public function handle(): int
    {
        $this->call(RabbitmqDeclareCommand::class);

        return self::SUCCESS;
    }
}
