<?php

namespace RrConcurrency\Commands;

use Illuminate\Console\Command;
use RrConcurrency\Services\JobsMonitor;

class JobsMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rr-concurrency:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(JobsMonitor $jobsMonitor)
    {
        $defaultWorkersCount = config('rr-concurrency.jobs.workers.number');
        $maxWorkersCount = config('rr-concurrency.jobs.workers.max_number');

        while (true) {
            $jobsMonitor->handle(
                pluginName: 'jobs',
                defaultWorkersCount: $defaultWorkersCount,
                maxWorkersCount: $maxWorkersCount,
            );

            usleep(1000);
        }
    }
}
