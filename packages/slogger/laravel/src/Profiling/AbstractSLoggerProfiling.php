<?php

namespace SLoggerLaravel\Profiling;

use Illuminate\Contracts\Foundation\Application;
use SLoggerLaravel\Profiling\Dto\SLoggerProfilingObjects;

abstract class AbstractSLoggerProfiling
{
    private ?bool $profilingEnabled = null;
    private bool $profilingStarted = false;

    abstract protected function onStart(): bool;

    abstract protected function onStop(): ?SLoggerProfilingObjects;

    public function __construct(private readonly Application $app)
    {
    }

    public function start(): void
    {
        if (!$this->profilingEnabled()) {
            return;
        }

        $this->profilingStarted = $this->onStart();
    }

    public function stop(): ?SLoggerProfilingObjects
    {
        if (!$this->profilingStarted || !$this->profilingEnabled()) {
            return null;
        }

        $result = $this->onStop();

        $this->profilingStarted = false;

        return $result;
    }

    private function profilingEnabled(): bool
    {
        if (is_null($this->profilingEnabled)) {
            $this->profilingEnabled = $this->app['config']['slogger.profiling.enabled'];
        }

        return $this->profilingEnabled;
    }
}
