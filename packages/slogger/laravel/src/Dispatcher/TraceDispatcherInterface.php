<?php

namespace SLoggerLaravel\Dispatcher;

interface TraceDispatcherInterface
{
    public function put(TraceDispatcherParameters $parameters): void;

    public function stop(): void;
}
