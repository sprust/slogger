<?php

namespace Tests\Packages\Sconcur\Database;

use Fiber;
use PHPUnit\Framework\TestCase;
use SConcur\Context\Context;
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
    protected function tearDown(): void
    {
        Context::current()->forget('sconcur.db.transactions');

        parent::tearDown();
    }

    public function testACommitOnlyRunsTheCallbacksOfItsOwnCoroutine(): void
    {
        $manager = new CoroutineTransactionsManager();

        $ran = [];

        foreach (['first', 'second'] as $name) {
            $coroutine = $this->coroutine(function () use ($manager, &$ran, $name): void {
                $manager->begin('mysql', 1);
                $manager->addCallback(function () use (&$ran, $name): void {
                    $ran[] = $name;
                });
            });

            $coroutine->start();
        }

        // A third coroutine opens and commits its own transaction on the same connection
        // name. On the shared manager this commit would find both neighbours' records
        // staged under 'mysql' and run their callbacks here.
        $committer = $this->coroutine(static function () use ($manager): void {
            $manager->begin('mysql', 1);
            $manager->commit('mysql', 1, 0);
        });

        $committer->start();

        $this->assertSame([], $ran, 'nobody else\'s afterCommit callbacks were executed');
    }

    public function testEachCoroutineKeepsItsOwnPendingTransactions(): void
    {
        $manager = new CoroutineTransactionsManager();

        $counts = [];

        foreach ([1, 3] as $index => $levels) {
            $coroutine = $this->coroutine(function () use ($manager, &$counts, $index, $levels): void {
                for ($level = 1; $level <= $levels; $level++) {
                    $manager->begin('mysql', $level);
                }

                $counts[$index] = $manager->getPendingTransactions()->count();
            });

            $coroutine->start();
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

        $manager->begin('mysql', 1);

        $seen = null;

        $child = $this->coroutine(function () use ($manager, &$seen): void {
            $seen = $manager->getPendingTransactions()->count();
        }, parentFiberId: State::currentContextFiberId());

        $child->start();

        $this->assertSame(1, $seen);
    }

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
