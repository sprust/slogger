<?php

declare(strict_types=1);

namespace SConcur\Laravel\Foundation;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use SConcur\Context\Context;

/**
 * Coroutine-scoped application: a single shared container whose request-scoped
 * services are resolved from the current coroutine's context instead of being
 * cloned/swapped per request.
 *
 * SKELETON. Ported from yangusik/laravel-spawn (AsyncApplication), adapted to
 * SConcur's Context::current(). Not wired into the app yet. The DB resolver and
 * the per-adapter scoping (router/translator/config/view/events) are still TODO.
 *
 * See docs/fiber-safe-laravel-bridge.ru.md §4.
 */
class AsyncApplication extends Application
{
    /**
     * Scoped services safe to proxy via offsetGet (Facades). Services passed to
     * typed PHP parameters must NOT be here — the proxy does not implement their types.
     */
    private const array FACADE_PROXIED_MAP = [
        'auth'    => true,
        'session' => true,
    ];

    private bool $asyncMode = false;

    /** @var array<string, Closure> user-registered scoped factories */
    private array $scopedBindings = [];

    /** @var array<string, int> config('sconcur.scoped_services') as alias => 1 */
    private array $scopedServiceCache = [];

    public function isAsyncModeEnabled(): bool
    {
        return $this->asyncMode;
    }

    public function enableAsyncMode(): void
    {
        $this->asyncMode = true;

        if ($this->resolved('config')) {
            $scoped                   = $this->make('config')->get('sconcur.scoped_services', []);
            $this->scopedServiceCache = array_flip($scoped);
        }
    }

    public function scopedSingleton(string $abstract, Closure $factory): void
    {
        $this->scopedBindings[$abstract] = $factory;
    }

    /**
     * 'request' is always resolvable so code checking bound('request') during
     * bootstrap (before any HTTP request) does not crash.
     */
    public function bound($abstract): bool
    {
        if ($this->getAlias($abstract) === 'request') {
            return true;
        }

        return parent::bound($abstract);
    }

    public function offsetGet($key): mixed
    {
        $alias = $this->getAlias($key);

        if ($this->asyncMode && isset(self::FACADE_PROXIED_MAP[$alias])) {
            return new ScopedServiceProxy(fn() => $this->tryResolveScoped($alias));
        }

        if ($alias === 'request') {
            return $this->resolveRequest();
        }

        return parent::offsetGet($key);
    }

    protected function resolve($abstract, $parameters = [], $raiseEvents = true)
    {
        $alias = $this->getAlias($abstract);

        if ($alias === 'request') {
            return $this->resolveRequest();
        }

        if ($this->asyncMode) {
            $instance = $this->tryResolveScoped($alias);

            if ($instance !== null) {
                return $instance;
            }
        }

        return parent::resolve($abstract, $parameters, $raiseEvents);
    }

    private function resolveRequest(): object
    {
        if ($this->asyncMode) {
            $fromContext = Context::current()->find(ScopedService::REQUEST->value);

            if ($fromContext !== null) {
                return $fromContext;
            }
        }

        return $this->instances['request'] ?? Request::createFromGlobals();
    }

    /**
     * Resolve a scoped service from the current coroutine context, or null if the
     * alias is not scoped.
     *
     * TODO: mirror laravel-spawn's afterResolving/fireResolvingCallbacks handling
     * for adapters registered via afterResolving('session', ...).
     */
    private function tryResolveScoped(string $alias): mixed
    {
        $key = ScopedService::tryFrom($alias);

        if ($key === null
            && !isset($this->scopedBindings[$alias])
            && !isset($this->scopedServiceCache[$alias])
        ) {
            return null;
        }

        $ctx    = Context::current();
        $ctxKey = $key?->value ?? $alias;

        $instance = $ctx->find($ctxKey);

        if ($instance !== null) {
            return $instance;
        }

        if (isset($this->scopedBindings[$alias])) {
            $instance = ($this->scopedBindings[$alias])($this);
        } else {
            $bindings = $this->getBindings();

            if (!isset($bindings[$alias])) {
                return null;
            }

            $concrete = $bindings[$alias]['concrete'];
            $instance = $concrete instanceof Closure ? $concrete($this) : $this->build($concrete);
        }

        $ctx->set($ctxKey, $instance);

        return $instance;
    }
}
