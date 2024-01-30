<?php

namespace App\Services\SLogger\Pushing;

use SLoggerLaravel\Dispatcher\SLoggerTraceDispatcher;
use SLoggerLaravel\Objects\SLoggerTraceObject;
use SLoggerLaravel\Objects\SLoggerTraceObjects;

class SLoggerTraceQueueDispatcher extends SLoggerTraceDispatcher
{
    protected function sendTraces(SLoggerTraceObject $parentTrace, array $traces): void
    {
        $traceObjects = new SLoggerTraceObjects();

        foreach ($traces as $trace) {
            $traceObjects->add($trace);
        }

        dispatch(new SLoggerTracePushingJob($traceObjects));
    }
}
