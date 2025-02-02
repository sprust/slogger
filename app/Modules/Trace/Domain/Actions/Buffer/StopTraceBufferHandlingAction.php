<?php

namespace App\Modules\Trace\Domain\Actions\Buffer;

use App\Modules\Trace\Contracts\Actions\StopTraceBufferHandlingActionInterface;
use App\Modules\Trace\Domain\Services\MonitorTraceBufferHandlingService;
use Symfony\Component\Console\Output\OutputInterface;

readonly class StopTraceBufferHandlingAction implements StopTraceBufferHandlingActionInterface
{
    public function __construct(
        private MonitorTraceBufferHandlingService $monitorTraceBufferHandlingService
    ) {
    }

    public function handle(OutputInterface $output): void
    {
        $output->writeln('Stop trace buffer handling');

        $this->monitorTraceBufferHandlingService->flush();

        $output->writeln('Stop signal sent');
    }
}
