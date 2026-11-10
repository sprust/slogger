<?php

declare (strict_types=1);

namespace Tests\Modules\Common\Infrastructure\Services\Fixtures;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $payload)
    {
    }
}
