<?php

namespace Tests\Packages\Sconcur\Database;

use Fiber;
use PHPUnit\Framework\TestCase;
use SConcur\Laravel\Database\CoroutineTransactionsManager;
use SConcur\State;

/**
 * Laravel's manager keys its records by connection name, not by whoever opened the
 * transaction, so under coroutines one commit runs everyone's afterCommit callbacks and
 * one rollback fires everyone's rollback callbacks. What is tested here is that two
 * coroutines never share a manager, and that a child still shares its parent's.
 */
class CoroutineTransactionsManagerTest extends TestCase
{
    public function testACommitOnlyRunsTheCallbacksOfItsOwnCoroutine(): void
    {
        $manager = new CoroutineTransactionsManager();

        $ran = [];

        foreach (['first', 'second'] as $name) {
            $this->runCoroutine(function () use ($manager, &$ran, $name): void {
                $manager->begin('mysql', 1);
                $manager->addCallback(function () use (&$ran, $name): void {
                    $ran[] = $name;
                });
            });
        }

        // A third coroutine opens and commits its own transaction on the same connection
        // name. On the shared manager this commit would find both neighbours' records
        // staged under 'mysql' and run their callbacks here.
        $this->runCoroutine(static function () use ($manager): void {
            $manager->begin('mysql', 1);
            $manager->commit('mysql', 1, 0);
        });

        $this->assertSame([], $ran, 'nobody else\'s afterCommit callbacks were executed');
    }

    public function testEachCoroutineKeepsItsOwnPendingTransactions(): void
    {
        $manager = new CoroutineTransactionsManager();

        $counts = [];

        foreach ([1, 3] as $index => $levels) {
            $this->runCoroutine(function () use ($manager, &$counts, $index, $levels): void {
                for ($level = 1; $level <= $levels; $level++) {
                    $manager->begin('mysql', $level);
                }

                $counts[$index] = $manager->getPendingTransactions()->count();
            });
        }

        $this->assertSame([1, 3], $counts);
        $this->assertSame(0, $manager->getPendingTransactions()->count(), 'the caller saw none of it');
    }

    /**
     * The other half: a coroutine spawned inside a transaction is inside that
     * transaction, so it has to see the manager holding it.
     */
    public function testAChildCoroutineSharesItsParentsManager(): void
    {
        $manager = new CoroutineTransactionsManager();

        $seen = null;

        $this->runCoroutine(function () use ($manager, &$seen): void {
            $manager->begin('mysql', 1);

            $this->runCoroutine(function () use ($manager, &$seen): void {
                $seen = $manager->getPendingTransactions()->count();
            }, parentFiberId: State::currentContextFiberId());
        });

        $this->assertSame(1, $seen);
    }

    /**
     * A caller with no fiber around it gets a manager of this object's own rather than one
     * in the root context — which every coroutine reads through, and would therefore all
     * share. It also means the class is safe to register in a process that never starts a
     * coroutine: there it behaves exactly like the framework's own.
     */
    public function testACallerOutsideACoroutineDoesNotShareWithCoroutines(): void
    {
        $manager = new CoroutineTransactionsManager();

        $manager->begin('mysql', 1);

        $seen = null;

        $this->runCoroutine(function () use ($manager, &$seen): void {
            $seen = $manager->getPendingTransactions()->count();
        });

        $this->assertSame(0, $seen, 'the coroutine did not inherit the synchronous caller\'s transaction');
        $this->assertSame(1, $manager->getPendingTransactions()->count(), 'and the caller kept its own');
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
