<?php

namespace RrParallel\Commands;

use Exception;
use Illuminate\Console\Command;
use RrParallel\Services\Drivers\Roadrunner\JobsMonitor;
use Throwable;

class StartJobsMonitorCommand extends Command
{
    use JobsMonitorTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rr-parallel:monitor:start {pluginName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start rr workers monitor by plugin name';

    /**
     * Execute the console command.
     *
     * @throws Exception
     * @throws Throwable
     */
    public function handle(): void
    {
        $pluginName = $this->argument('pluginName');

        $workersNumber = (int) config("rr-parallel.monitor.$pluginName.number");

        if (!$workersNumber) {
            throw new Exception(
                "No number of workers defined for plugin $pluginName. Config rr-parallel.monitor.$pluginName.*."
            );
        }

        $workersMaxNumber = (int) config("rr-parallel.monitor.$pluginName.max_number");
        $workersMaxNumber = $workersMaxNumber ?: ($workersNumber * 2);

        $tryCount = 5;

        $this->forgetStopSignal($pluginName);

        $monitor = app(JobsMonitor::class);

        while (true) {
            if ($this->isTimeToStop($pluginName)) {
                break;
            }

            try {
                $monitor->handle(
                    pluginName: $pluginName,
                    defaultWorkersCount: $workersNumber,
                    maxWorkersCount: $workersMaxNumber,
                );

                $tryCount = 5;
            } catch (Throwable $exception) {
                --$tryCount;

                if ($tryCount <= 0) {
                    throw $exception;
                }

                $monitor = app(JobsMonitor::class);
            }

            sleep(1);
        }
    }
}
