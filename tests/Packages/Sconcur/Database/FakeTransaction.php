<?php

namespace Tests\Packages\Sconcur\Database;

use SConcur\Features\Sql\Results\ExecResult;
use SConcur\Features\Sql\Transaction;

/**
 * A Transaction that records instead of talking to the extension.
 *
 * It deliberately does not call the parent constructor: building a real one needs
 * a payload envelope and a task key from the Go side, and none of the behaviour
 * under test depends on them. __destruct is neutralised for the same reason.
 */
class FakeTransaction extends Transaction
{
    public int $commits = 0;

    public int $rollbacks = 0;

    /** @var list<string> */
    public array $statements = [];

    public function __construct()
    {
    }

    public function commit(): void
    {
        $this->commits++;
    }

    public function rollback(): void
    {
        $this->rollbacks++;
    }

    public function exec(string $sql, array $bindings = []): ExecResult
    {
        $this->statements[] = $sql;

        return new ExecResult(affectedRows: 0, lastInsertId: 0);
    }

    public function __destruct()
    {
    }
}
