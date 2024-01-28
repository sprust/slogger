<?php

namespace SLoggerLaravel\Dispatcher;

interface TraceDispatcherInterface
{
    public function push(TracePushDispatcherParameters $parameters): void;

    public function stop(TraceStopDispatcherParameters $parameters): void;
}
