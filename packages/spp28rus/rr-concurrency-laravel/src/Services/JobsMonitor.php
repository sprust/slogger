<?php

namespace RrConcurrency\Services;

use RrConcurrency\Services\Roadrunner\RpcFactory;
use Spiral\RoadRunner\WorkerPool;

readonly class JobsMonitor
{
    private WorkerPool $pool;

    private int $dangerFreePercent;
    private int $percentStep;

    public function __construct(RpcFactory $rpcFactory)
    {
        $this->pool = new WorkerPool(
            $rpcFactory->getRpc()
        );

        $this->dangerFreePercent = 30;
        $this->percentStep       = 100 - $this->dangerFreePercent;
    }

    public function handle(string $pluginName, int $defaultWorkersCount, int $maxWorkersCount): void
    {
        $workers = $this->pool->getWorkers($pluginName);

        $totalCount = $workers->count();

        if ($totalCount > $maxWorkersCount) {
            $removingCount = $totalCount - $maxWorkersCount;

            dump("- removing too many: $removingCount");

            while ($removingCount--) {
                $this->pool->removeWorker($pluginName);
            }

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

                if ($workingPercentByDefault < $this->percentStep) {
                    $removingCount = $totalCount - $defaultWorkersCount;

                    dump("- removing free: $removingCount");

                    while ($removingCount--) {
                        $this->pool->removeWorker($pluginName);
                    }
                }
            }

            return;
        }

        $addingCount = ceil($totalCount / 100 * $this->dangerFreePercent);

        dump("+ adding: $addingCount");

        while ($addingCount--) {
            $this->pool->addWorker($pluginName);
        }
    }
}
