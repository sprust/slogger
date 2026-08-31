<?php

namespace Tests\Packages\Sconcur\Database;

use Fiber;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SConcur\Laravel\Database\Mysql\TransactionStack;
use SConcur\Laravel\Database\TransactionStore;
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
    /**
     * One store per test, shared by every stack it makes — the way a Connection owns one
     * store for its own name. Nothing leaks between tests, and the synchronous path is
     * not the root context, so a stack opened outside a fiber cannot be inherited.
     */
    private ?TransactionStore $store = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new TransactionStore();
    }

    public function testThereIsNoLevelUntilSomethingBegins(): void
    {
        $stack = $this->stack();

        $this->assertSame(0, $stack->level());
        $this->assertNull($stack->transaction());
    }

    public function testBeginOpensTheRootLevel(): void
    {
        $stack       = $this->stack();
        $transaction = new FakeTransaction();

        $stack->begin($transaction);

        $this->assertSame(1, $stack->level());
        $this->assertSame($transaction, $stack->transaction());
        $this->assertNull($stack->savepointAt(1));
    }

    public function testNestedLevelsRememberTheirSavepoint(): void
    {
        $stack = $this->stack();
        $stack->begin(new FakeTransaction());

        $stack->pushSavepoint($stack->nextSavepointName());
        $stack->pushSavepoint($stack->nextSavepointName());

        $this->assertSame(3, $stack->level());
        $this->assertSame('sc_sp_1', $stack->savepointAt(2));
        $this->assertSame('sc_sp_2', $stack->savepointAt(3));
    }

    public function testTruncatingDropsEveryLevelAboveTheGivenOne(): void
    {
        $stack = $this->stack();
        $stack->begin(new FakeTransaction());
        $stack->pushSavepoint($stack->nextSavepointName());
        $stack->pushSavepoint($stack->nextSavepointName());

        $stack->truncateTo(1);

        $this->assertSame(1, $stack->level());
    }

    public function testPoppingTheLastLevelClearsTheEntry(): void
    {
        $stack = $this->stack();
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

        $this->runCoroutine(function () use (&$seen): void {
            $stack = $this->stack();
            $stack->begin(new FakeTransaction());

            $seen['first'] = $stack->level();
        });

        $this->runCoroutine(function () use (&$seen): void {
            $seen['second'] = $this->stack()->level();
        });

        $this->assertSame(1, $seen['first']);
        $this->assertSame(0, $seen['second']);
        $this->assertSame(0, $this->stack()->level(), 'and nothing was left behind for the caller');
    }

    /**
     * The other half: a coroutine spawned inside a transaction joins it, which is
     * what makes a fan-out inside DB::transaction() atomic instead of quietly
     * autocommitting beside it.
     */
    public function testAChildCoroutineInheritsTheTransaction(): void
    {
        $transaction = new FakeTransaction();

        $seen = null;

        $this->runCoroutine(function () use ($transaction, &$seen): void {
            $this->stack()->begin($transaction);

            $this->runCoroutine(function () use (&$seen): void {
                $seen = $this->stack()->transaction();
            }, parentFiberId: State::currentContextFiberId());
        });

        $this->assertSame($transaction, $seen);
    }

    /**
     * A child that closed the root level would commit on the Go side and leave the
     * opener's own context entry behind, pointing at a transaction that no longer
     * exists. Hence the owner check.
     */
    public function testAChildCoroutineCannotCloseTheInheritedTransaction(): void
    {
        $caught = null;

        $this->runCoroutine(function () use (&$caught): void {
            $this->stack()->begin(new FakeTransaction());

            $this->runCoroutine(function () use (&$caught): void {
                try {
                    $this->stack()->assertOwner('commit');
                } catch (RuntimeException $exception) {
                    $caught = $exception;
                }
            }, parentFiberId: State::currentContextFiberId());
        });

        $this->assertInstanceOf(RuntimeException::class, $caught);
        $this->assertStringContainsString('did not open it', $caught->getMessage());
    }

    public function testTheOwnerMayCloseItsOwnTransaction(): void
    {
        $this->runCoroutine(function (): void {
            $stack = $this->stack();
            $stack->begin(new FakeTransaction());

            $stack->assertOwner('commit');
        });

        $this->addToAssertionCount(1);
    }

    /**
     * A nested level opened by a child is the child's own: writes go to its map,
     * so the parent keeps the level count it had.
     */
    public function testANestedLevelInAChildDoesNotChangeTheParentsLevel(): void
    {
        $parentLevel = null;

        $this->runCoroutine(function () use (&$parentLevel): void {
            $stack = $this->stack();
            $stack->begin(new FakeTransaction());

            $this->runCoroutine(function (): void {
                $childStack = $this->stack();

                $childStack->pushSavepoint($childStack->nextSavepointName());
            }, parentFiberId: State::currentContextFiberId());

            $parentLevel = $stack->level();
        });

        $this->assertSame(1, $parentLevel);
    }

    /**
     * Names come from a counter on the shared state, not from the caller's depth.
     * By depth both children would say `2` and MySQL would drop the first
     * savepoint when the second reused its name — one child's rollback would then
     * land on the other's.
     */
    public function testSavepointNamesDoNotCollideBetweenSiblingCoroutines(): void
    {
        $names = [];

        $this->runCoroutine(function () use (&$names): void {
            $this->stack()->begin(new FakeTransaction());

            $parentFiberId = State::currentContextFiberId();

            foreach (['first', 'second'] as $key) {
                $this->runCoroutine(function () use (&$names, $key): void {
                    $names[$key] = $this->stack()->nextSavepointName();
                }, parentFiberId: $parentFiberId);
            }
        });

        $this->assertSame('sc_sp_1', $names['first']);
        $this->assertSame('sc_sp_2', $names['second']);
    }

    private function stack(): TransactionStack
    {
        return new TransactionStack('mysql', $this->store);
    }

    /**
     * Runs the body as a coroutine, with its context parent registered the way the runtime
     * does when it spawns one — and released the way the runtime releases it.
     *
     * The release matters here: a context is keyed by the fiber's spl_object_id, and PHP
     * reuses those once a fiber is collected. A helper that only registered would let one
     * test's state reappear under another test's coroutine.
     */
    private function runCoroutine(callable $body, ?int $parentFiberId = null): void
    {
        $fiber = new Fiber($body);

        $fiberId = spl_object_id($fiber);

        State::registerCoroutineContext($fiberId, $parentFiberId ?? State::currentContextFiberId());

        try {
            $fiber->start();
        } finally {
            State::unRegisterFiber($fiberId);
        }
    }
}
