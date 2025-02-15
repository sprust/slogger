<?php

namespace App\Console\Commands\Cron;

class CronStopCommand extends BaseCronCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:stop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stop the cron';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->forgetSessionKey();

        $this->components->info('Broadcasting cron stop signal');

        return self::SUCCESS;
    }
}
