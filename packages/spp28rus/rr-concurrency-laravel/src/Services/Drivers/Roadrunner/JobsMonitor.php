<?php

namespace RrConcurrency\Services\Drivers\Roadrunner;

use Illuminate\Contracts\Foundation\Application;
use Laravel\Octane\DispatchesEvents;
use RrConcurrency\Events\MonitorAddedWorkersEvent;
use RrConcurrency\Events\MonitorRemovedExcessWorkersEvent;
use RrConcurrency\Events\MonitorRemovedFreeWorkersEvent;
use Spiral\RoadRunner\WorkerPool;

class JobsMonitor
{
    use DispatchesEvents;

    private readonly WorkerPool $pool;
    private readonly Application $app;

    private readonly int $dangerFreePercent;
    private readonly int $percentStep;
    private readonly int $removingPeriodicityInSeconds;
    private int $workersCountEditedTime;

    public function __construct(RpcFactory $rpcFactory, Application $app)
    {
        $this->pool = new WorkerPool(
            $rpcFactory->getRpc()
        );
        $this->app  = $app;

        $this->dangerFreePercent            = 30;
        $this->percentStep                  = 100 - $this->dangerFreePercent;
        $this->removingPeriodicityInSeconds = 10;
        $this->workersCountEditedTime       = time();
    }

    public function handle(string $pluginName, int $defaultWorkersCount, int $maxWorkersCount): void
    {
        $workers = $this->pool->getWorkers($pluginName);

        $totalCount = $workers->count();

        if ($totalCount > $maxWorkersCount) {
            $removingCount = $totalCount - $maxWorkersCount;

            dump("- removing excess: $removingCount");

            $index = $removingCount;

            while ($index--) {
                $this->pool->removeWorker($pluginName);
            }

            $this->dispatchEvent(
                app: $this->app,
                event: new MonitorRemovedExcessWorkersEvent(
                    count: $removingCount,
                    currentTotalCount: $totalCount
                )
            );

            return;
        }

        $readyCount   = 0;
        $workingCount = 0;

        foreach ($workers->getWorkers() as $worker) {
            if ($worker->status === 'ready') {
                ++$readyCount;
            }
            if ($worker->status === 'working') {
                ++$workingCount;
            }
        }

        $freePercent = $readyCount / $totalCount * 100;

        if ($freePercent > $this->dangerFreePercent) {
            if ($totalCount > $defaultWorkersCount) {
                $workingPercentByDefault = $workingCount / $defaultWorkersCount * 100;

                if ($workingPercentByDefault < $this->percentStep
                    && (time() - $this->workersCountEditedTime) > $this->removingPeriodicityInSeconds
                ) {
                    $removingCount = $totalCount - $defaultWorkersCount;

                    $removingCount = ceil($removingCount * .2);

                    dump("- removing free: $removingCount");

                    $index = $removingCount;

                    while ($index--) {
                        $this->pool->removeWorker($pluginName);
                    }

                    $this->dispatchEvent(
                        app: $this->app,
                        event: new MonitorRemovedFreeWorkersEvent(
                            count: $removingCount,
                            currentTotalCount: $totalCount
                        )
                    );

                    $this->workersCountEditedTime = time();
                }
            }

            return;
        }

        $addingCount = ceil($totalCount / 100 * $this->dangerFreePercent);

        dump("+ adding: $addingCount");

        $index = $addingCount;

        while ($index--) {
            $this->pool->addWorker($pluginName);
        }

        $this->dispatchEvent(
            app: $this->app,
            event: new MonitorAddedWorkersEvent(
                count: $addingCount,
                currentTotalCount: $totalCount
            )
        );

        $this->workersCountEditedTime = time();
    }
}
