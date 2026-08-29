<?php

namespace Tests\Packages\Sconcur\Database;

use Fiber;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SConcur\Context\Context;
use SConcur\Laravel\Database\Mysql\TransactionStack;
use SConcur\State;

/**
 * The stack is what replaces Connection::$transactions, a property of an object
 * shared by every coroutine in the process. What matters is therefore not only
 * that the levels come out right, but who can see them and who may close them.
 *
 * Coroutines here are plain Fibers with their context parent registered by hand —
 * the same thing WaitGroup does — so the semantics can be tested without the
 * extension loaded.
 */
class TransactionStackTest extends TestCase
{
    protected function tearDown(): void
    {
        // The root context (fiber id 0) outlives the process, unlike a coroutine's.
        Context::current()->forget('sconcur.db.tx.mysql');

        parent::tearDown();
    }

    public function testThereIsNoLevelUntilSomethingBegins(): void
    {
        $stack = new TransactionStack('mysql');

        $this->assertSame(0, $stack->level());
        $this->assertNull($stack->transaction());
    }

    public function testBeginOpensTheRootLevel(): void
    {
        $stack       = new TransactionStack('mysql');
        $transaction = new FakeTransaction();

        $stack->begin($transaction);

        $this->assertSame(1, $stack->level());
        $this->assertSame($transaction, $stack->transaction());
        $this->assertNull($stack->savepointAt(1));
    }

    public function testNestedLevelsRememberTheirSavepoint(): void
    {
        $stack = new TransactionStack('mysql');
        $stack->begin(new FakeTransaction());

        $stack->pushSavepoint($stack->nextSavepointName());
        $stack->pushSavepoint($stack->nextSavepointName());

        $this->assertSame(3, $stack->level());
        $this->assertSame('sc_sp_1', $stack->savepointAt(2));
        $this->assertSame('sc_sp_2', $stack->savepointAt(3));
    }

    public function testTruncatingDropsEveryLevelAboveTheGivenOne(): void
    {
        $stack = new TransactionStack('mysql');
        $stack->begin(new FakeTransaction());
        $stack->pushSavepoint($stack->nextSavepointName());
        $stack->pushSavepoint($stack->nextSavepointName());

        $stack->truncateTo(1);

        $this->assertSame(1, $stack->level());
    }

    public function testPoppingTheLastLevelClearsTheEntry(): void
    {
        $stack = new TransactionStack('mysql');
        $stack->begin(new FakeTransaction());

        $stack->pop();

        $this->assertSame(0, $stack->level());
        $this->assertNull($stack->transaction());
    }

    /**
     * The whole point: two concurrent requests are siblings in the context tree,
     * never ancestors, so neither can see the other's transaction.
     */
    public function testSiblingCoroutinesDoNotSeeEachOthersTransaction(): void
    {
        $seen = [];

        $first = $this->coroutine(function () use (&$seen): void {
            $stack = new TransactionStack('mysql');
            $stack->begin(new FakeTransaction());

            $seen['first'] = $stack->level();
        });

        $second = $this->coroutine(function () use (&$seen): void {
            $seen['second'] = (new TransactionStack('mysql'))->level();
        });

        $first->start();
        $second->start();

        $this->assertSame(1, $seen['first']);
        $this->assertSame(0, $seen['second']);
        $this->assertSame(0, (new TransactionStack('mysql'))->level(), 'the root context stays clean');
    }

    /**
     * The other half: a coroutine spawned inside a transaction joins it, which is
     * what makes a fan-out inside DB::transaction() atomic instead of quietly
     * autocommitting beside it.
     */
    public function testAChildCoroutineInheritsTheTransaction(): void
    {
        $stack       = new TransactionStack('mysql');
        $transaction = new FakeTransaction();
        $stack->begin($transaction);

        $seen = null;

        $child = $this->coroutine(function () use (&$seen): void {
            $seen = (new TransactionStack('mysql'))->transaction();
        }, parentFiberId: State::currentContextFiberId());

        $child->start();

        $this->assertSame($transaction, $seen);
    }

    /**
     * A child that closed the root level would commit on the Go side and leave the
     * opener's own context entry behind, pointing at a transaction that no longer
     * exists. Hence the owner check.
     */
    public function testAChildCoroutineCannotCloseTheInheritedTransaction(): void
    {
        $stack = new TransactionStack('mysql');
        $stack->begin(new FakeTransaction());

        $caught = null;

        $child = $this->coroutine(function () use (&$caught): void {
            try {
                (new TransactionStack('mysql'))->assertOwner('commit');
            } catch (RuntimeException $exception) {
                $caught = $exception;
            }
        }, parentFiberId: State::currentContextFiberId());

        $child->start();

        $this->assertInstanceOf(RuntimeException::class, $caught);
        $this->assertStringContainsString('did not open it', $caught->getMessage());
    }

    public function testTheOwnerMayCloseItsOwnTransaction(): void
    {
        $stack = new TransactionStack('mysql');
        $stack->begin(new FakeTransaction());

        $stack->assertOwner('commit');

        $this->addToAssertionCount(1);
    }

    /**
     * A nested level opened by a child is the child's own: writes go to its map,
     * so the parent keeps the level count it had.
     */
    public function testANestedLevelInAChildDoesNotChangeTheParentsLevel(): void
    {
        $stack = new TransactionStack('mysql');
        $stack->begin(new FakeTransaction());

        $child = $this->coroutine(function (): void {
            $childStack = new TransactionStack('mysql');

            $childStack->pushSavepoint($childStack->nextSavepointName());
        }, parentFiberId: State::currentContextFiberId());

        $child->start();

        $this->assertSame(1, $stack->level());
    }

    /**
     * Names come from a counter on the shared state, not from the caller's depth.
     * By depth both children would say `2` and MySQL would drop the first
     * savepoint when the second reused its name — one child's rollback would then
     * land on the other's.
     */
    public function testSavepointNamesDoNotCollideBetweenSiblingCoroutines(): void
    {
        $stack = new TransactionStack('mysql');
        $stack->begin(new FakeTransaction());

        $parentFiberId = State::currentContextFiberId();

        $names = [];

        foreach (['first', 'second'] as $key) {
            $child = $this->coroutine(function () use (&$names, $key): void {
                $names[$key] = (new TransactionStack('mysql'))->nextSavepointName();
            }, parentFiberId: $parentFiberId);

            $child->start();
        }

        $this->assertSame('sc_sp_1', $names['first']);
        $this->assertSame('sc_sp_2', $names['second']);
    }

    /**
     * Registers the context parent the way the runtime does when it spawns a
     * coroutine, so reads walk up the chain exactly as they would in a WaitGroup.
     */
    private function coroutine(callable $body, ?int $parentFiberId = null): Fiber
    {
        $fiber = new Fiber($body);

        State::registerCoroutineContext(
            spl_object_id($fiber),
            $parentFiberId ?? State::currentContextFiberId(),
        );

        return $fiber;
    }
}
