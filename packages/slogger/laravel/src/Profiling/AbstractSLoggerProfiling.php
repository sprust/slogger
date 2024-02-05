<?php

namespace SLoggerLaravel\Profiling;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use SLoggerLaravel\Profiling\Dto\SLoggerProfilingObject;
use SLoggerLaravel\Profiling\Dto\SLoggerProfilingObjects;

abstract class AbstractSLoggerProfiling
{
    private bool $profilingEnabled;
    private bool $profilingStarted = false;

    private array $namespaces;

    abstract protected function onStart(): bool;

    abstract protected function onStop(): ?SLoggerProfilingObjects;

    public function __construct(private readonly Application $app)
    {
        $this->profilingEnabled = $this->app['config']['slogger.profiling.enabled'];
        $this->namespaces       = $this->app['config']['slogger.profiling.namespaces'];
    }

    public function start(): void
    {
        if (!$this->profilingEnabled) {
            return;
        }

        $this->profilingStarted = $this->onStart();
    }

    public function stop(): ?SLoggerProfilingObjects
    {
        if (!$this->profilingStarted || !$this->profilingEnabled) {
            return null;
        }

        $profilingObjects = $this->onStop();

        $filteredProfilingObjects = new SLoggerProfilingObjects();

        foreach ($profilingObjects->getItems() as $profilingObject) {
            if (!$this->needProfiling($profilingObject->calling)
                && !$this->needProfiling($profilingObject->callable)
            ) {
                continue;
            }

            $filteredProfilingObjects->add($profilingObject);
        }

        $this->profilingStarted = false;

        return $filteredProfilingObjects;
    }

    protected function needProfiling(string $method): bool
    {
        return Str::is($this->namespaces, $method);
    }
}
