<?php

namespace App\Modules\Trace\Domain\Actions\Buffer;

use App\Modules\Trace\Contracts\Actions\StartTraceBufferHandlingActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceBufferInvalidRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceBufferRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Domain\Services\MonitorTraceBufferHandlingService;
use App\Modules\Trace\Repositories\Dto\Trace\TraceBufferDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceBufferInvalidDto;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

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

        while ($this->monitorTraceBufferHandlingService->isProcessKeyActual($processKey)) {
            $traces = $this->traceBufferRepository->findForHandling(
                page: 1,
                perPage: 20,
                deadTimeLine: now()->subMinutes(50) // TODO: move to config?
            );

            $currentTracesCount        = count($traces->traces);
            $currentInvalidTracesCount = count($traces->invalidTraces);

            $currentAllTracesCount = $currentTracesCount + $currentInvalidTracesCount;

            if (!$currentAllTracesCount) {
                continue;
            }

            $totalInvalidCount += $currentInvalidTracesCount;

            $totalCount += $currentAllTracesCount;

            /** @var string[] $allTraceIds */
            $allTraceIds = [];

            /** @var TraceBufferInvalidDto[] $traces */
            $currentInvalidTraces = [];

            if ($currentTracesCount) {
                array_map(
                    static function (TraceBufferDto $trace) use (&$allTraceIds) {
                        $allTraceIds[] = $trace->traceId;
                    },
                    $traces->traces
                );

                $insertedTraces = array_filter(
                    $traces->traces,
                    static fn(TraceBufferDto $trace) => $trace->inserted
                );

                $skippedTraces = array_filter(
                    $traces->traces,
                    static fn(TraceBufferDto $trace) => !$trace->inserted
                );

                $currentInsertedTracesCount = count($insertedTraces);
                $currentSkippedTracesCount  = count($skippedTraces);

                $totalInsertedCount += $currentInsertedTracesCount;

                $this->traceRepository->createManyByBuffers(
                    traceBuffers: $insertedTraces
                );

                if ($currentSkippedTracesCount) {
                    $totalSkippedCount += $currentSkippedTracesCount;

                    array_map(
                        static function (TraceBufferDto $trace) use (&$currentInvalidTraces) {
                            $exception = null;

                            try {
                                $document = (array) $trace;
                            } catch (Throwable $exception) {
                                $document = [];
                            }

                            $currentInvalidTraces[] = new TraceBufferInvalidDto(
                                traceId: $trace->traceId,
                                document: $document,
                                error: 'not inserted' . (is_null($exception)
                                    ? ''
                                    : ", convert trace error: {$exception->getMessage()}"
                                ),
                            );
                        },
                        $skippedTraces
                    );
                }

                array_map(
                    static function (TraceBufferInvalidDto $trace) use (&$currentInvalidTraces, &$allTraceIds) {
                        $currentInvalidTraces[] = $trace;

                        if (!$trace->traceId) {
                            return;
                        }

                        $allTraceIds[] = $trace->traceId;
                    },
                    $traces->invalidTraces
                );
            }

            if (count($currentInvalidTraces)) {
                $this->traceBufferInvalidRepository->createMany(
                    invalidTraceBuffers: $currentInvalidTraces
                );
            }

            $deletedCount = $this->traceBufferRepository->delete(
                traceIds: $allTraceIds
            );

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
