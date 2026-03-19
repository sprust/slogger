<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Buffer;

use App\Modules\Trace\Contracts\Actions\StartTraceBufferHandlingActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceBufferInvalidRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceBufferRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Domain\Services\MonitorTraceBufferHandlingService;
use Symfony\Component\Console\Output\OutputInterface;

readonly class StartTraceBufferHandlingAction implements StartTraceBufferHandlingActionInterface
{
    public function __construct(
        private MonitorTraceBufferHandlingService $monitorTraceBufferHandlingService,
        private TraceBufferRepositoryInterface $traceBufferRepository,
        private TraceRepositoryInterface $traceRepository,
        private TraceBufferInvalidRepositoryInterface $traceBufferInvalidRepository,
    ) {
    }

    public function handle(OutputInterface $output): void
    {
        $output->writeln('Start trace buffer handling');

        $totalCount        = 0;
        $totalHandledCount = 0;
        $totalSkippedCount = 0;
        $totalInvalidCount = 0;

        $processKey = $this->monitorTraceBufferHandlingService->startProcess();

        $processMonitorTime = time();

        while (true) {
            if (time() - $processMonitorTime > 2) {
                if (!$this->monitorTraceBufferHandlingService->isProcessKeyActual($processKey)) {
                    break;
                }

                $processMonitorTime = time();
            }

            $traces = $this->traceBufferRepository->findForHandling(
                page: 1,
                perPage: 100,
            );

            $currentTracesCount = count($traces->traces)
                + count($traces->creatingTraces)
                + count($traces->updatingTraces);

            $currentInvalidCount = count($traces->invalidTraces);

            $currentTotalCount = $currentTracesCount + $currentInvalidCount;

            if ($currentTotalCount === 0) {
                usleep(500);

                continue;
            }

            $totalCount        += $currentTotalCount;
            $totalInvalidCount += $currentInvalidCount;

            $deletedCount = 0;

            $handledBufferIds    = [];
            $processingBufferIds = [];

            if ($currentTracesCount > 0) {
                foreach ($traces->traces as $trace) {
                    if ($trace->inserted && $trace->updated) {
                        $handledBufferIds[] = $trace->id;
                    } else {
                        $processingBufferIds[] = $trace->id;
                    }
                }

                $this->traceRepository->freshManyByBuffers(
                    traceBuffers: $traces->traces
                );

                if (count($processingBufferIds)) {
                    $this->traceBufferRepository->markAsHandled(
                        ids: $processingBufferIds
                    );
                }
            }

            $this->traceRepository->freshManyByCreatingUpdatingBuffers(
                creating: $traces->creatingTraces,
                updating: $traces->updatingTraces,
            );

            foreach (array_merge($traces->creatingTraces, $traces->updatingTraces) as $trace) {
                $handledBufferIds[] = $trace->id;
            }

            if (count($traces->invalidTraces) > 0) {
                $this->traceBufferInvalidRepository->createMany(
                    invalidTraceBuffers: $traces->invalidTraces
                );

                foreach ($traces->invalidTraces as $invalidTrace) {
                    $handledBufferIds[] = $invalidTrace->id;
                }
            }

            $handledCount = count($handledBufferIds);

            $totalHandledCount += $handledCount;

            if ($handledCount) {
                $deletedCount += $this->traceBufferRepository->delete(
                    ids: $handledBufferIds
                );
            }

            // TODO: statistic
            $output->writeln(
                sprintf(
                    'tot: %d, hand: %d, sk: %d, inv: %d, del: %d',
                    $totalCount,
                    $totalHandledCount,
                    $totalSkippedCount,
                    $totalInvalidCount,
                    $deletedCount
                )
            );
        }

        $output->writeln('Stop trace buffer handling');
    }
}
