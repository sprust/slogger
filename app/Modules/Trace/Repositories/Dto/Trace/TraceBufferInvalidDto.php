<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Trace;

readonly class TraceBufferInvalidDto
{
    /**
     * @param array<string, mixed> $document
     */
    public function __construct(
        public ?string $traceId,
        public array $document,
        public string $error,
    ) {
    }
}
