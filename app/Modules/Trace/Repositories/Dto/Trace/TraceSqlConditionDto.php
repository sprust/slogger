<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Trace;

/**
 * A WHERE condition over the traces table and the values of the `{name:Type}`
 * placeholders in it.
 */
readonly class TraceSqlConditionDto
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public string $sql,
        public array $params,
    ) {
    }
}
