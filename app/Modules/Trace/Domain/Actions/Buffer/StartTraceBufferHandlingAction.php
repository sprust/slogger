<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Buffer;

use App\Modules\Service\Contracts\Actions\FindServicesActionInterface;
use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Trace\Contracts\Actions\StartTraceBufferHandlingActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceBufferInvalidRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceBufferRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Domain\Services\MonitorTraceBufferHandlingService;
use SConcur\WaitGroup;
use Symfony\Component\Console\Output\OutputInterface;

readonly class StartTraceBufferHandlingAction implements StartTraceBufferHandlingActionInterface
{
    public function __construct(
        private MonitorTraceBufferHandlingService $monitorTraceBufferHandlingService,
        private FindServicesActionInterface $findServicesAction,
        private TraceBufferRepositoryInterface $traceBufferRepository,
        private TraceRepositoryInterface $traceRepository,
        private TraceBufferInvalidRepositoryInterface $traceBufferInvalidRepository,
    ) {
    }

    public function handle(OutputInterface $output): void
    {
        $output->writeln('Start trace buffer handling');

        /**
         * @var array{
         *  total: int,
         *  handled: int,
         *  invalid: int,
         * } $serviceCounts
         */
        $serviceCounts = [];

        $processKey = $this->monitorTraceBufferHandlingService->startProcess();

        $processMonitorTime = time();
        $serviceUpdateTime  = time();

        $serviceIds = null;

        while (true) {
            if ($serviceIds === null || (time() - $serviceUpdateTime) > 5) {
                $serviceIds = array_map(
                    static fn(ServiceObject $service) => $service->id,
                    $this->findServicesAction->handle(),
                );

                $serviceUpdateTime = time();
            }

            /** @var int[] $serviceIds */

            if (count($serviceIds) === 0) {
                $output->writeln('Service ids is empty');

                sleep(1);

                continue;
            }

            if (time() - $processMonitorTime > 2) {
                if (!$this->monitorTraceBufferHandlingService->isProcessKeyActual($processKey)) {
                    break;
                }

                $processMonitorTime = time();
            }

            $waitGroup = WaitGroup::create();

            foreach ($serviceIds as $serviceId) {
                $waitGroup->add(
                    function () use ($serviceId, $output, &$serviceCounts): int {
                        $serviceCounts[$serviceId] ??= [
                            'total' => 0,
                            'handled' => 0,
                            'invalid' => 0,
                        ];

                        $traces = $this->traceBufferRepository->findForHandling(
                            page: 1,
                            perPage: 100,
                            serviceId: $serviceId,
                        );

                        $currentTracesCount = count($traces->traces)
                            + count($traces->creatingTraces)
                            + count($traces->updatingTraces);

                        $currentInvalidCount = count($traces->invalidTraces);

                        $currentTotalCount = $currentTracesCount + $currentInvalidCount;

                        if ($currentTotalCount === 0) {
                            return 0;
                        }

                        $serviceCounts[$serviceId]['total']   += $currentTotalCount;
                        $serviceCounts[$serviceId]['invalid'] += $currentInvalidCount;

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

                        $serviceCounts[$serviceId]['handled'] += $handledCount;

                        if ($handledCount) {
                            $deletedCount += $this->traceBufferRepository->delete(
                                ids: $handledBufferIds
                            );
                        }

                        // TODO: statistic
                        $output->writeln(
                            sprintf(
                                'sid %d: tot: %d, hand: %d, inv: %d, del: %d',
                                $serviceId,
                                $serviceCounts[$serviceId]['total'],
                                $serviceCounts[$serviceId]['handled'],
                                $serviceCounts[$serviceId]['invalid'],
                                $deletedCount
                            )
                        );

                        return $currentTotalCount;
                    }
                );
            }

            $servicesTotalTracesCount = 0;

            foreach ($waitGroup->waitResults() as $serviceTotalTracesCount) {
                $servicesTotalTracesCount += $serviceTotalTracesCount;
            }

            if ($servicesTotalTracesCount === 0) {
                usleep(100);
            }
        }

        $output->writeln('Stop trace buffer handling');
    }
}
