<?php

declare(strict_types=1);

namespace App\Services\Clickhouse;

/**
 * The `database.connections.clickhouse` entry: where the HTTP interface is and who
 * talks to it.
 */
readonly class ClickhouseConnectionConfig
{
    public function __construct(
        public string $host,
        public int $port,
        public string $database,
        public string $username,
        public string $password,
        public int $timeoutSeconds,
    ) {
    }
}
