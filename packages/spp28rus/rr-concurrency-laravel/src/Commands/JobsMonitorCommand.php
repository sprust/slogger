<?php

namespace RrConcurrency\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use RrConcurrency\Services\Drivers\Roadrunner\JobsMonitor;

class JobsMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rr-concurrency:monitor {operation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private string $cacheKeyStop = 'rr-concurrency-monitor-status';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(JobsMonitor $jobsMonitor): void
    {
        $operation = $this->argument('operation');

        Cache::delete($this->cacheKeyStop);

        if ($operation === 'start') {
            $defaultWorkersCount = config('rr-concurrency.workers.number');
            $maxWorkersCount     = config('rr-concurrency.workers.max_number');

            while (true) {
                if (Cache::has($this->cacheKeyStop)) {
                    break;
                }

                $jobsMonitor->handle(
                    pluginName: 'jobs',
                    defaultWorkersCount: $defaultWorkersCount,
                    maxWorkersCount: $maxWorkersCount,
                );

                sleep(1);
            }
        } elseif ($operation === 'stop') {
            Cache::set($this->cacheKeyStop, true);
        } else {
            throw new Exception("Unknown operation: $operation");
        }
    }
}
