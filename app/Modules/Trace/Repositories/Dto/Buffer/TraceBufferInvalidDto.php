<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Buffer;

readonly class TraceBufferInvalidDto
{
    /**
     * @param array<string, mixed> $document
     */
    public function __construct(
        public string $id,
        public ?string $traceId,
        public array $document,
        public string $error,
    ) {
    }
}
