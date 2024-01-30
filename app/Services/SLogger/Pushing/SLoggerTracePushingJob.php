<?php

namespace App\Services\SLogger\Pushing;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use SLoggerLaravel\HttpClient\SLoggerHttpClient;
use SLoggerLaravel\Objects\SLoggerTraceObjects;

class SLoggerTracePushingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(private readonly SLoggerTraceObjects $traceObjects)
    {
        $this->onConnection(config('queue.queues.pushing.connection'))
            ->onQueue(config('queue.queues.pushing.name'));
    }

    /**
     * @throws GuzzleException
     */
    public function handle(SLoggerHttpClient $loggerHttpClient): void
    {
        $loggerHttpClient->sendTraces($this->traceObjects);
    }
}
