<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Exceptions\Manager;

readonly class JsonHttpExceptionResponseObject
{
    public function __construct(
        public string $message,
        public int $httpStatus,
        public ?string $errorCode = null,
        public string $messageField = 'message',
    ) {
    }
}
