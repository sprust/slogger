<?php

namespace SLoggerLaravel\Dispatcher;

interface SLoggerTraceDispatcherInterface
{
    public function push(SLoggerTracePushDispatcherParameters $parameters): void;

    public function stop(SLoggerTraceStopDispatcherParameters $parameters): void;
}
