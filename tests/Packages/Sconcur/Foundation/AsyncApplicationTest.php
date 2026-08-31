<?php

namespace Tests\Packages\Sconcur\Foundation;

use Fiber;
use Illuminate\Config\Repository;
use PHPUnit\Framework\TestCase;
use SConcur\Laravel\Foundation\AsyncApplication;
use SConcur\State;
use stdClass;

/**
 * The container resolves the scoped aliases per coroutine, and there is no mode to enter
 * — so what has to hold is that concurrent coroutines never share one, and that a caller
 * with no coroutine around it does not leave one where they would all find it.
 */
class AsyncApplicationTest extends TestCase
{
    public function testTwoCoroutinesEachGetTheirOwnScopedInstance(): void
    {
        $app = $this->application();

        $seen = [];

        foreach (['first', 'second'] as $name) {
            $this->runCoroutine(function () use ($app, &$seen, $name): void {
                $seen[$name] = $app->make('auth');
            });
        }

        $this->assertNotSame($seen['first'], $seen['second']);
    }

    public function testOneCoroutineKeepsTheInstanceItBuilt(): void
    {
        $app = $this->application();

        $same = null;

        $this->runCoroutine(function () use ($app, &$same): void {
            $same = $app->make('auth') === $app->make('auth');
        });

        $this->assertTrue($same);
    }

    /**
     * The one the class is easiest to get wrong. Outside a coroutine `Context::current()`
     * is the process root, which is never released and which every coroutine reads
     * through — so an instance built during bootstrap or in a start command's own body
     * would be handed to every request, message and task for the life of the process.
     */
    public function testAnInstanceBuiltOutsideACoroutineIsNotInheritedByOne(): void
    {
        $app = $this->application();

        $atRoot = $app->make('auth');

        $inCoroutine = null;

        $this->runCoroutine(function () use ($app, &$inCoroutine): void {
            $inCoroutine = $app->make('auth');
        });

        $this->assertNotSame($atRoot, $inCoroutine);
        $this->assertSame($atRoot, $app->make('auth'), 'and the caller keeps its own');
    }

    /** Bootstrap code asks this before any request exists; it must not crash. */
    public function testRequestIsAlwaysResolvable(): void
    {
        $this->assertTrue($this->application()->bound('request'));
    }

    /**
     * An alias named in config('sconcur.scoped_services') is scoped like the built-in
     * ones. The list is read once the config repository exists — and asking before that
     * must not freeze an empty list for the life of the process.
     */
    public function testAConfiguredAliasBecomesScopedOnceTheConfigExists(): void
    {
        $app = $this->application(config: false);

        $app->bind('some.manager', static fn(): stdClass => new stdClass());

        $this->assertNotSame(
            $app->make('some.manager'),
            $app->make('some.manager'),
            'with no config yet it is an ordinary binding, built anew each time'
        );

        $app->instance('config', new Repository(['sconcur' => ['scoped_services' => ['some.manager']]]));

        $first  = null;
        $second = null;

        $this->runCoroutine(function () use ($app, &$first): void {
            $first = $app->make('some.manager');
        });

        $this->runCoroutine(function () use ($app, &$second): void {
            $second = $app->make('some.manager');
        });

        $this->assertNotSame($first, $second, 'the list was read, not remembered as empty');
    }

    private function application(bool $config = true): AsyncApplication
    {
        $app = new AsyncApplication('/app');

        if ($config) {
            $app->instance('config', new Repository(['sconcur' => ['scoped_services' => []]]));
        }

        // A binding, not an instance: tryResolveScoped builds from the binding, which is
        // what makes a scoped alias a new object per coroutine.
        $app->bind('auth', static fn(): stdClass => new stdClass());

        return $app;
    }

    private function runCoroutine(callable $body): void
    {
        $fiber = new Fiber($body);

        $fiberId = spl_object_id($fiber);

        State::registerCoroutineContext($fiberId, State::currentContextFiberId());

        try {
            $fiber->start();
        } finally {
            State::unRegisterFiber($fiberId);
        }
    }
}
