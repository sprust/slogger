<?php

namespace Tests\Packages\Sconcur\Database;

use SConcur\Features\Mysql\Connection as SqlConnection;
use SConcur\Features\Sql\Results\ExecResult;
use SConcur\Features\Sql\Transaction;
use SConcur\Laravel\Database\Mysql\Connection;
use Throwable;

/**
 * The connection with its four boundary methods recorded instead of executed, the
 * way QueueTest intercepts publish(). Everything above them — run(), the events,
 * the grammars, the transaction stack — is the real thing.
 *
 * `target` is captured with every call so a test can tell a statement that went to
 * the pooled connection from one that went to the open transaction.
 */
class FakeConnection extends Connection
{
    /** @var list<array{sql: string, bindings: list<mixed>, target: object}> */
    public array $queries = [];

    /** @var list<array{sql: string, bindings: list<mixed>, target: object}> */
    public array $statements = [];

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public ?ExecResult $execResult = null;

    public ?Throwable $execThrows = null;

    public ?Throwable $fetchThrows = null;

    public ?FakeTransaction $openedTransaction = null;

    public static function make(): self
    {
        return new self(
            sql: new SqlConnection('user:pass@tcp(127.0.0.1:3306)/app'),
            database: 'app',
            tablePrefix: '',
            config: [
                'name'   => 'mysql',
                'driver' => 'sconcur_mysql',
            ],
        );
    }

    /** @return list<string> */
    public function statementSqls(): array
    {
        return array_column($this->statements, 'sql');
    }

    protected function fetchRows(string $query, array $bindings): array
    {
        $this->queries[] = [
            'sql'      => $query,
            'bindings' => array_values($bindings),
            'target'   => $this->target(),
        ];

        if ($this->fetchThrows !== null) {
            throw $this->fetchThrows;
        }

        return $this->rows;
    }

    protected function streamRows(string $query, array $bindings): iterable
    {
        return $this->fetchRows($query, $bindings);
    }

    protected function execStatement(string $query, array $bindings): ExecResult
    {
        $this->statements[] = [
            'sql'      => $query,
            'bindings' => array_values($bindings),
            'target'   => $this->target(),
        ];

        if ($this->execThrows !== null) {
            throw $this->execThrows;
        }

        return $this->execResult ?? new ExecResult(affectedRows: 0, lastInsertId: 0);
    }

    protected function openTransaction(): Transaction
    {
        return $this->openedTransaction = new FakeTransaction();
    }
}
