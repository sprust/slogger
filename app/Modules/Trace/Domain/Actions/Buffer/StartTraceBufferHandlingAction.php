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

        $totalCount         = 0;
        $totalInsertedCount = 0;
        $totalSkippedCount  = 0;
        $totalInvalidCount  = 0;

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
                perPage: 20,
            );

            $currentTracesCount  = count($traces->traces);
            $currentInvalidCount = count($traces->invalidTraces);

            $currentTotalCount = $currentTracesCount + $currentInvalidCount;

            if ($currentTotalCount === 0) {
                usleep(100);

                continue;
            }

            $totalCount        += $currentTotalCount;
            $totalInvalidCount += $currentInvalidCount;

            $deletedCount = 0;

            // TODO: collision between insert, update and handling

            if (count($traces->traces) > 0) {
                $completedTraceIds  = [];
                $processingTraceIds = [];

                foreach ($traces->traces as $trace) {
                    if ($trace->inserted && $trace->updated) {
                        $completedTraceIds[] = $trace->traceId;
                    } else {
                        $processingTraceIds[] = $trace->traceId;
                    }
                }

                $this->traceRepository->freshManyByBuffers(
                    traceBuffers: $traces->traces
                );

                if (count($completedTraceIds)) {
                    $deletedCount += $this->traceBufferRepository->delete(
                        traceIds: $completedTraceIds
                    );
                }

                if (count($processingTraceIds)) {
                    $this->traceBufferRepository->markAsHandled(
                        traceIds: $processingTraceIds
                    );
                }
            }

            if (count($traces->invalidTraces) > 0) {
                $this->traceBufferInvalidRepository->createMany(
                    invalidTraceBuffers: $traces->invalidTraces
                );
            }

            // TODO: statistic
            $output->writeln(
                sprintf(
                    'tot: %d, ins: %d, sk: %d, inv: %d, del: %d',
                    $totalCount,
                    $totalInsertedCount,
                    $totalSkippedCount,
                    $totalInvalidCount,
                    $deletedCount
                )
            );
        }

        $output->writeln('Stop trace buffer handling');
    }
}
