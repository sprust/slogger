<?php

namespace App\Console\Commands\Cron;

class CronRestartCommand extends BaseCronCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:restart';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restart the cron';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->setRestartFlag(true);

        $this->components->info('Broadcasting cron restart signal');

        return self::SUCCESS;
    }
}
