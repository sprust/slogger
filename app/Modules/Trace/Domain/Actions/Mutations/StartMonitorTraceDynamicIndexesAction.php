<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Services\MonitorTraceDynamicIndexesService;
use App\Modules\Trace\Repositories\TraceDynamicIndexRepository;
use App\Modules\Trace\Repositories\TraceRepository;
use Illuminate\Support\Carbon;
use Throwable;

readonly class StartMonitorTraceDynamicIndexesAction
{
    public function __construct(
        private TraceDynamicIndexRepository $traceDynamicIndexRepository,
        private TraceRepository $traceRepository,
        private MonitorTraceDynamicIndexesService $monitorTraceDynamicIndexesService
    ) {
    }

    public function handle(): void
    {
        $this->monitorTraceDynamicIndexesService->setStopFlag(false);

        $deletingTimeout = time() + 30;

        while (!$this->monitorTraceDynamicIndexesService->hasRestartFlag()) {
            if ($deletingTimeout < time()) {
                $indexes = $this->traceDynamicIndexRepository->find(
                    limit: 10,
                    inProcess: false,
                    toActualUntilAt: Carbon::now()
                );

                foreach ($indexes as $index) {
                    if ($index->created) {
                        $this->traceRepository->deleteIndexByName(
                            indexName: $index->indexName,
                            collectionNames: $index->collectionNames
                        );
                    }

                    $this->traceDynamicIndexRepository->deleteById(
                        id: $index->id
                    );
                }

                $deletingTimeout = time() + 30;
            }

            $indexes = $this->traceDynamicIndexRepository->find(
                limit: 10,
                inProcess: true,
            );

            if (empty($indexes)) {
                sleep(1);

                continue;
            }

            foreach ($indexes as $index) {
                $exception = null;

                try {
                    $indexCreated = $this->traceRepository->createIndex(
                        name: $index->indexName,
                        collectionNames: $index->collectionNames,
                        fields: $index->fields
                    );
                } catch (Throwable $exception) {
                    $indexCreated = false;
                }

                $this->traceDynamicIndexRepository->updateByName(
                    name: $index->name,
                    inProcess: false,
                    created: $indexCreated,
                    exception: $exception
                );
            }
        }
    }
}
