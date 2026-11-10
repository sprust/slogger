<?php

declare (strict_types=1);

namespace Tests\Modules\Common\Infrastructure\Services\Fixtures;

use Fiber;
use Illuminate\Bus\Dispatcher;
use Throwable;

class RecordingBusDispatcher extends Dispatcher
{
    /** @var array<object> */
    public array $jobs           = [];

    public ?Throwable $exception = null;

    public bool $suspend         = false;

    public function dispatch($command): void
    {
        if ($this->suspend) {
            Fiber::suspend();
        }

        if ($this->exception !== null) {
            throw $this->exception;
        }

        $this->jobs[] = $command;
    }
}
