<?php

namespace SLoggerLaravel\Dispatcher\Queue\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use SLoggerLaravel\Dispatcher\Queue\ApiClients\SLoggerApiClientInterface;
use SLoggerLaravel\SLoggerProcessor;
use Throwable;

abstract class AbstractSLoggerTraceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    abstract protected function onHandle(SLoggerApiClientInterface $apiClient): void;

    public int $tries = 120;

    public int $backoff = 1;

    public function __construct()
    {
        $this->onConnection(config('slogger.dispatchers.queue.connection'))
            ->onQueue(config('slogger.dispatchers.queue.name'));
    }

    /**
     * @throws Throwable
     */
    public function handle(SLoggerProcessor $loggerProcessor, SLoggerApiClientInterface $apiClient): void
    {
        try {
            $loggerProcessor->handleWithoutTracing(
                fn() => $this->onHandle($apiClient)
            );
        } catch (Throwable $exception) {
            if ($this->job->attempts() < $this->tries) {
                $this->job->release($this->backoff);
            } else {
                $this->job->delete();

                Log::channel(config('slogger.log_channel'))
                    ->error($exception->getMessage(), [
                        'code'  => $exception->getCode(),
                        'file'  => $exception->getFile(),
                        'line'  => $exception->getLine(),
                        'trace' => $exception->getTraceAsString(),
                    ]);
            }
        }
    }
}
