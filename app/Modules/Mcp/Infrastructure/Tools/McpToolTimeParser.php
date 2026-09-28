<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use Illuminate\Support\Carbon;
use Throwable;

readonly class McpToolTimeParser
{
    private const string ISO_8601 = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|[+-]\d{2}:?\d{2})?$/';

    public function parse(string $value): ?Carbon
    {
        if (!preg_match(self::ISO_8601, $value)) {
            return null;
        }

        try {
            return new Carbon($value, 'UTC');
        } catch (Throwable) {
            return null;
        }
    }
}
