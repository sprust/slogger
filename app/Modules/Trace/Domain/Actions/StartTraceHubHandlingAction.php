<?php

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Contracts\Actions\StartTraceHubHandlingActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceHubInvalidRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceHubRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Repositories\Dto\Trace\TraceHubDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceHubInvalidDto;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

readonly class StartTraceHubHandlingAction implements StartTraceHubHandlingActionInterface
{
    public function __construct(
        private TraceHubRepositoryInterface $traceHubRepository,
        private TraceRepositoryInterface $traceRepository,
        private TraceHubInvalidRepositoryInterface $traceHubInvalidRepository,
    ) {
    }

    public function handle(OutputInterface $output): void
    {
        $totalCount         = 0;
        $totalInsertedCount = 0;
        $totalSkippedCount  = 0;
        $totalInvalidCount  = 0;

        // TODO: stop signal feature
        while (true) {
            $traces = $this->traceHubRepository->findForHandling(
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

            /** @var TraceHubInvalidDto[] $traces */
            $currentInvalidTraces = [];

            if ($currentTracesCount) {
                array_map(
                    static function (TraceHubDto $trace) use (&$allTraceIds) {
                        $allTraceIds[] = $trace->traceId;
                    },
                    $traces->traces
                );

                $insertedTraces = array_filter(
                    $traces->traces,
                    static fn(TraceHubDto $trace) => $trace->inserted
                );

                $skippedTraces = array_filter(
                    $traces->traces,
                    static fn(TraceHubDto $trace) => !$trace->inserted
                );

                $currentInsertedTracesCount = count($insertedTraces);
                $currentSkippedTracesCount  = count($skippedTraces);

                $totalInsertedCount += $currentInsertedTracesCount;

                $this->traceRepository->createManyByHubs(
                    traceHubs: $insertedTraces
                );

                if ($currentSkippedTracesCount) {
                    $totalSkippedCount += $currentSkippedTracesCount;

                    array_map(
                        static function (TraceHubDto $trace) use (&$currentInvalidTraces) {
                            $exception = null;

                            try {
                                $document = (array) $trace;
                            } catch (Throwable $exception) {
                                $document = [];
                            }

                            $currentInvalidTraces[] = new TraceHubInvalidDto(
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
                    static function (TraceHubInvalidDto $trace) use (&$currentInvalidTraces, &$allTraceIds) {
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
                $this->traceHubInvalidRepository->createMany(
                    invalidTraceHubs: $currentInvalidTraces
                );
            }

            $deletedCount = $this->traceHubRepository->delete(
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
    }
}
