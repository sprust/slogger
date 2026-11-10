<?php

declare (strict_types=1);

namespace Tests\Modules\Common\Infrastructure\Services\Fixtures;

use Psr\Log\AbstractLogger;
use Stringable;

class RecordingLogger extends AbstractLogger
{
    /** @var array<array{level: mixed, message: string|Stringable, context: array<string, mixed>}> */
    public array $records = [];

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level'   => $level,
            'message' => $message,
            'context' => $context,
        ];
    }
}
