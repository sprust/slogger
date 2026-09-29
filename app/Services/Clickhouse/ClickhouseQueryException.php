<?php

declare(strict_types=1);

namespace App\Services\Clickhouse;

use RuntimeException;
use Throwable;

/**
 * A query ClickHouse refused or could not finish, or a request that never reached it.
 *
 * The code is ClickHouse's own error code (`X-ClickHouse-Exception-Code`), 0 when the
 * server did not give one.
 */
class ClickhouseQueryException extends RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        public readonly ?string $queryId = null,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            message: $message,
            code: $code,
            previous: $previous
        );
    }
}
