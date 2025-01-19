<?php

namespace SLoggerLaravel\Dispatcher\Queue;

use Illuminate\Queue\Console\WorkCommand;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class QueueManager
{
    private readonly int $workersNum;

    private LoggerInterface $logger;

    private string $workerCommand;

    private bool $shouldQuit = false;

    public function __construct()
    {
        $this->workersNum = config('slogger.dispatchers.queue.workers_num');

        $this->logger = Log::channel(config('slogger.log_channel'));

        $this->workerCommand = sprintf(
            '%s %s/artisan %s %s --queue=%s --tries=%d --backoff=%d',
            (new PhpExecutableFinder)->find(),
            base_path(),
            app(WorkCommand::class)->getName(),
            config('slogger.dispatchers.queue.connection'),
            config('slogger.dispatchers.queue.name'),
            120,
            1
        );

        pcntl_async_signals(true);

        pcntl_signal(SIGQUIT, fn() => $this->shouldQuit = true);
        pcntl_signal(SIGTERM, fn() => $this->shouldQuit = true);
    }

    public function start(OutputInterface $output): void
    {
        /** @var array<int, Process> $processes */
        $processes = [];

        for ($index = 0; $index < $this->workersNum; $index++) {
            $process = $this->createProcess();

            $process->start();

            $processes[$index] = $process;
        }

        while (true) {
            if ($this->shouldQuit) {
                foreach ($processes as $process) {
                    if (!$process->isRunning()) {
                        continue;
                    }

                    $process->signal(SIGQUIT);
                }

                break;
            }

            foreach ($processes as $index => $process) {
                if ($process->isRunning()) {
                    $this->readOutput($output, $process);

                    continue;
                }

                $this->logger->error(
                    'Worker process is stopped',
                    ['exit_code' => $process->getExitCode()]
                );

                $processes[$index] = $this->createProcess();
                $processes[$index]->start();
            }

            sleep(1);
        }
    }

    private function createProcess(): Process
    {
        return Process::fromShellCommandline($this->workerCommand)
            ->setTimeout(null);
    }

    private function readOutput(OutputInterface $output, Process $process): void
    {
        $outputs = [
            $process->getIncrementalOutput(),
            $process->getIncrementalErrorOutput(),
        ];

        $process->clearOutput()->clearErrorOutput();

        $message = trim(implode(PHP_EOL, array_filter($outputs)), PHP_EOL);

        if (!$message) {
            return;
        }

        $output->writeln($message);
    }
}
