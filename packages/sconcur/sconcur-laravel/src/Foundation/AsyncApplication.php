<?php

declare(strict_types=1);

namespace SConcur\Laravel\Foundation;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use SConcur\Context\Context;

/**
 * Coroutine-scoped application: a single shared container whose request-scoped
 * services are resolved from the current coroutine's context instead of being
 * cloned/swapped per request.
 *
 * There is no mode to turn on. It used to have one, and the switch was the bug: it had to
 * be thrown by whoever knew the process would run coroutines, which meant three call
 * sites, one of which (the task pool) never did it — and before that, a check on argv
 * that stopped matching the moment the master began passing a group's flags ahead of the
 * command name. Nothing detects anything now.
 *
 * Outside a coroutine this costs nothing: Context::current() resolves to the process root,
 * so every scoped service is a single instance for a single caller — which is what the
 * container would have given anyway.
 *
 * Ported from yangusik/laravel-spawn (AsyncApplication), adapted to SConcur's
 * Context::current(). See docs/fiber-safe-laravel-bridge.ru.md §4.
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

    /** @var array<string, Closure> user-registered scoped factories */
    private array $scopedBindings = [];

    /**
     * config('sconcur.scoped_services') as alias => 1, read once the config repository
     * exists. Null until then — a resolve during bootstrap must not freeze an empty list
     * for the life of the process.
     *
     * @var array<string, int>|null
     */
    private ?array $scopedServices = null;

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

        if (isset(self::FACADE_PROXIED_MAP[$alias])) {
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

        $instance = $this->tryResolveScoped($alias);

        if ($instance !== null) {
            return $instance;
        }

        return parent::resolve($abstract, $parameters, $raiseEvents);
    }

    private function resolveRequest(): object
    {
        $fromContext = Context::current()->find(ScopedService::REQUEST->value);

        if ($fromContext !== null) {
            return $fromContext;
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
            && !$this->isConfiguredScoped($alias)
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

    /**
     * Whether the application asked for this alias to be scoped, in
     * config('sconcur.scoped_services').
     *
     * Read straight from the stored instance rather than through make(): resolving the
     * config repository goes through resolve(), which asks this question, which would
     * ask for the repository again.
     */
    private function isConfiguredScoped(string $alias): bool
    {
        if ($this->scopedServices === null) {
            $config = $this->instances['config'] ?? null;

            if (!$config instanceof ConfigRepository) {
                return false;
            }

            $this->scopedServices = array_flip((array) $config->get('sconcur.scoped_services', []));
        }

        return isset($this->scopedServices[$alias]);
    }
}
