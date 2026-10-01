<?php

declare(strict_types=1);

namespace Tests\Services\Clickhouse;

use App\Services\Clickhouse\ClickhouseClient;
use Throwable;

/**
 * Records the queries it is sent and answers each select with the next prepared rows, or
 * throws the next prepared exception.
 */
class FakeClickhouseClient extends ClickhouseClient
{
    /** @var list<array{sql: string, params: array<string, mixed>, queryIdPrefix: string, settings: array<string, int|string>}> */
    public array $selects = [];

    /** @var list<array{sql: string, params: array<string, mixed>}> */
    public array $commands = [];

    /**
     * @param list<list<array<string, mixed>>|Throwable> $answers the rows of each select, in order
     */
    public function __construct(
        private array $answers = []
    ) {
    }

    public function select(string $sql, array $params = [], string $queryIdPrefix = 'query', array $settings = []): array
    {
        $this->selects[] = [
            'sql'           => $sql,
            'params'        => $params,
            'queryIdPrefix' => $queryIdPrefix,
            'settings'      => $settings,
        ];

        $answer = array_shift($this->answers) ?? [];

        if ($answer instanceof Throwable) {
            throw $answer;
        }

        return $answer;
    }

    public function command(string $sql, array $params = [], string $queryIdPrefix = 'command'): void
    {
        $this->commands[] = [
            'sql'    => $sql,
            'params' => $params,
        ];
    }
}
