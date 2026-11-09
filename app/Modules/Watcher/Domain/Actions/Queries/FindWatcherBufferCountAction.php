<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Actions\Queries\FindTraceBufferCountAction;

readonly class FindWatcherBufferCountAction
{
    public function __construct(
        private FindTraceBufferCountAction $findTraceBufferCountAction
    ) {
    }

    public function handle(): int
    {
        return $this->findTraceBufferCountAction->handle();
    }
}
