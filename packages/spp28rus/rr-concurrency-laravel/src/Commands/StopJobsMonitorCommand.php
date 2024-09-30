<?php

namespace RrConcurrency\Commands;

use Exception;
use Illuminate\Console\Command;
use Throwable;

class StopJobsMonitorCommand extends Command
{
    use JobsMonitorTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rr-concurrency:monitor:stop {pluginName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stop rr workers monitor by plugin name';

    /**
     * Execute the console command.
     *
     * @throws Exception
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->setStopSignalToStop(
            $this->argument('pluginName'),
        );
    }
}
