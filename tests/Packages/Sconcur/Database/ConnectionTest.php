<?php

namespace Tests\Packages\Sconcur\Database;

use DateTimeImmutable;
use Fiber;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Events\Dispatcher;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SConcur\Context\Context;
use SConcur\Exceptions\CoroutineTimeoutException;
use SConcur\Exceptions\FlowStoppedException;
use SConcur\Exceptions\TaskErrorException;
use SConcur\Features\Sql\Results\ExecResult;
use SConcur\State;
use Throwable;

class ConnectionTest extends TestCase
{
    protected function tearDown(): void
    {
        Context::current()->forget('sconcur.db.tx.mysql');
        Context::current()->forget('sconcur.db.last_insert_id.mysql');

        parent::tearDown();
    }

    public function testSelectHandsRowsBackAsObjects(): void
    {
        $connection       = FakeConnection::make();
        $connection->rows = [['id' => 1, 'name' => 'Ann']];

        $rows = $connection->select('select * from users');

        $this->assertCount(1, $rows);
        $this->assertSame(1, $rows[0]->id);
        $this->assertSame('Ann', $rows[0]->name);
    }

    /**
     * prepareBindings is what turns a DateTime into the grammar's format and a
     * bool into an int; skipping it would send the driver values it cannot bind.
     */
    public function testBindingsArePreparedBeforeTheyLeave(): void
    {
        $connection = FakeConnection::make();

        $connection->select('select * from users where created_at > ? and active = ?', [
            new DateTimeImmutable('2026-08-29 10:00:00'),
            true,
        ]);

        $this->assertSame(['2026-08-29 10:00:00', 1], $connection->queries[0]['bindings']);
    }

    public function testCursorStreamsRowsAsObjects(): void
    {
        $connection       = FakeConnection::make();
        $connection->rows = [['id' => 1], ['id' => 2]];

        $ids = [];

        foreach ($connection->cursor('select id from users') as $row) {
            $ids[] = $row->id;
        }

        $this->assertSame([1, 2], $ids);
    }

    public function testAffectingStatementReturnsTheAffectedRowCount(): void
    {
        $connection             = FakeConnection::make();
        $connection->execResult = new ExecResult(affectedRows: 3, lastInsertId: 0);

        $this->assertSame(3, $connection->update('update users set name = ?', ['Ann']));
    }

    public function testInsertRemembersTheGeneratedId(): void
    {
        $connection             = FakeConnection::make();
        $connection->execResult = new ExecResult(affectedRows: 1, lastInsertId: 42);

        $this->assertTrue($connection->insert('insert into users (name) values (?)', ['Ann']));
        $this->assertSame(42, $connection->getLastInsertId());
    }

    /**
     * MySqlProcessor reads the id straight after the insert. A property would be
     * readable by a coroutine that inserted in between — the task pool preempts on
     * a timer, so no call between the two lines is needed for that to happen.
     */
    public function testTheGeneratedIdIsPrivateToItsCoroutine(): void
    {
        $connection             = FakeConnection::make();
        $connection->execResult = new ExecResult(affectedRows: 1, lastInsertId: 7);

        $seen = [];

        foreach ([7, 8] as $index => $id) {
            $connection->execResult = new ExecResult(affectedRows: 1, lastInsertId: $id);

            $fiber = new Fiber(function () use ($connection, &$seen, $index): void {
                $connection->insert('insert into users (name) values (?)', ['Ann']);

                $seen[$index] = $connection->getLastInsertId();
            });

            $fiberId = spl_object_id($fiber);

            State::registerCoroutineContext($fiberId, State::currentContextFiberId());

            try {
                $fiber->start();
            } finally {
                // Released as the runtime would: contexts are keyed by spl_object_id, and
                // PHP reuses those, so a leftover would surface under the next coroutine.
                State::unRegisterFiber($fiberId);
            }
        }

        $this->assertSame([7, 8], $seen);
        $this->assertNull($connection->getLastInsertId(), 'nothing leaked into the caller');
    }

    // -------------------------------------------------------------- transactions

    public function testBeginOpensATransactionAndCommitClosesIt(): void
    {
        $connection = FakeConnection::make();

        $connection->beginTransaction();

        $this->assertSame(1, $connection->transactionLevel());

        $connection->commit();

        $this->assertSame(1, $connection->openedTransaction->commits);
        $this->assertSame(0, $connection->transactionLevel());
    }

    public function testStatementsRunInsideTheOpenTransaction(): void
    {
        $connection = FakeConnection::make();

        $connection->beginTransaction();
        $connection->statement('delete from users');

        $this->assertSame($connection->openedTransaction, end($connection->statements)['target']);
    }

    public function testANestedTransactionBecomesASavepoint(): void
    {
        $connection = FakeConnection::make();

        $connection->beginTransaction();
        $connection->beginTransaction();

        $this->assertSame(2, $connection->transactionLevel());
        $this->assertSame(['SAVEPOINT sc_sp_1'], $connection->statementSqls());
        $this->assertSame(
            $connection->openedTransaction,
            $connection->statements[0]['target'],
            'the savepoint belongs to the transaction, not to the pool'
        );
    }

    public function testCommittingANestedLevelLeavesTheTransactionOpen(): void
    {
        $connection = FakeConnection::make();

        $connection->beginTransaction();
        $connection->beginTransaction();
        $connection->commit();

        $this->assertSame(1, $connection->transactionLevel());
        $this->assertSame(0, $connection->openedTransaction->commits);
    }

    public function testRollingBackANestedLevelRollsBackToItsSavepoint(): void
    {
        $connection = FakeConnection::make();

        $connection->beginTransaction();
        $connection->beginTransaction();
        $connection->rollBack();

        $this->assertSame(1, $connection->transactionLevel());
        $this->assertSame(
            ['SAVEPOINT sc_sp_1', 'ROLLBACK TO SAVEPOINT sc_sp_1'],
            $connection->statementSqls()
        );
        $this->assertSame(0, $connection->openedTransaction->rollbacks);
    }

    public function testRollingBackTheRootLevelRollsBackTheTransaction(): void
    {
        $connection = FakeConnection::make();

        $connection->beginTransaction();
        $connection->rollBack();

        $this->assertSame(1, $connection->openedTransaction->rollbacks);
        $this->assertSame(0, $connection->transactionLevel());
    }

    public function testTransactionCommitsAndReturnsTheCallbackResult(): void
    {
        $connection = FakeConnection::make();

        $result = $connection->transaction(fn() => 'done');

        $this->assertSame('done', $result);
        $this->assertSame(1, $connection->openedTransaction->commits);
        $this->assertSame(0, $connection->transactionLevel());
    }

    public function testAThrowingCallbackRollsTheTransactionBack(): void
    {
        $connection = FakeConnection::make();

        try {
            $connection->transaction(function (): void {
                throw new RuntimeException('boom');
            });

            $this->fail('the exception should have been rethrown');
        } catch (RuntimeException $exception) {
            $this->assertSame('boom', $exception->getMessage());
        }

        $this->assertSame(1, $connection->openedTransaction->rollbacks);
        $this->assertSame(0, $connection->transactionLevel());
    }

    // -------------------------------------------------------------- diagnostics

    public function testTheQueryExecutedEventStillFires(): void
    {
        $connection = FakeConnection::make();

        $events = new Dispatcher();

        $seen = null;

        $events->listen(QueryExecuted::class, function (QueryExecuted $event) use (&$seen): void {
            $seen = $event;
        });

        $connection->setEventDispatcher($events);

        $connection->select('select * from users where id = ?', [1]);

        $this->assertInstanceOf(QueryExecuted::class, $seen);
        $this->assertSame('select * from users where id = ?', $seen->sql);
        $this->assertSame([1], $seen->bindings);
    }

    /**
     * MySqlConnection matches PDO's wording for 1062; the Go driver reports the
     * server's own. firstOrCreate() and createOrFirst() key off this class.
     */
    public function testTheDriversDuplicateEntryErrorIsRecognised(): void
    {
        $connection             = FakeConnection::make();
        $connection->execThrows = new TaskErrorException(
            "Error 1062 (23000): Duplicate entry 'ann@example.com' for key 'users.users_email_unique'"
        );

        $this->expectException(UniqueConstraintViolationException::class);

        $connection->insert('insert into users (email) values (?)', ['ann@example.com']);
    }

    /**
     * Illuminate wraps anything a statement throws in a QueryException. The runtime's own
     * unwind must not be wrapped: every `catch (FlowStoppedException)` in the consumer,
     * the task loop and the trace actions would stop recognising it and go on to do more
     * work — a failed_jobs write, a republish — on a coroutine that has no flow left.
     */
    public function testTheRuntimesUnwindIsNotWrappedInAQueryException(): void
    {
        foreach ([new FlowStoppedException('stopped'), new CoroutineTimeoutException('late')] as $unwind) {
            $connection = FakeConnection::make();

            $connection->fetchThrows = $unwind;

            try {
                $connection->select('select 1');

                $this->fail('the unwind should have been rethrown');
            } catch (Throwable $thrown) {
                $this->assertSame($unwind, $thrown, get_class($unwind) . ' came out as ' . get_class($thrown));
            }
        }
    }

    public function testAnOrdinaryFailureIsStillAQueryException(): void
    {
        $connection = FakeConnection::make();

        $connection->fetchThrows = new RuntimeException('syntax error');

        $this->expectException(QueryException::class);

        $connection->select('select 1');
    }

    public function testThereIsNoPdoHandle(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no PDO handle');

        FakeConnection::make()->getPdo();
    }

    public function testMultipleResultSetsAreRefused(): void
    {
        $this->expectException(RuntimeException::class);

        FakeConnection::make()->selectResultSets('call some_procedure()');
    }
}
