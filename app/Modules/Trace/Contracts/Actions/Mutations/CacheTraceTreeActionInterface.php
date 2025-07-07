<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Mutations;

interface CacheTraceTreeActionInterface
{
    public function handle(string $traceId): void;
}
