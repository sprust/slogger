<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Exceptions;

use Exception;

class TraceDynamicIndexInProcessException extends Exception
{
    public function __construct(
        public readonly string $indexId,
    ) {
        parent::__construct(
            'Trace dynamic index in process.'
        );
    }
}
