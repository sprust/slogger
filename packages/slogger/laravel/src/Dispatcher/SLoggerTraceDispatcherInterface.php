<?php

namespace SLoggerLaravel\Dispatcher;

use SLoggerLaravel\Objects\SLoggerTraceObject;
use SLoggerLaravel\Objects\SLoggerTraceStopObject;

interface SLoggerTraceDispatcherInterface
{
    public function push(SLoggerTraceObject $parameters): void;

    public function stop(SLoggerTraceStopObject $parameters): void;
}
