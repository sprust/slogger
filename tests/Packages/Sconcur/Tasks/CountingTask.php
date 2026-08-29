<?php

namespace Tests\Packages\Sconcur\Tasks;

use Closure;
use RuntimeException;
use SConcur\Laravel\Tasks\TaskInterface;
use SConcur\Laravel\Tasks\TickResultEnum;

/**
 * A task that does nothing but count its ticks, and throws on demand.
 */
class CountingTask implements TaskInterface
{
    public int $ticks = 0;

    /** @var list<TickResultEnum> */
    public array $results = [];

    public bool $throws = false;

    /** Runs before the tick is counted; lets a test drive the state from inside a tick. */
    public ?Closure $onTick = null;

    public function name(): string
    {
        return 'counting';
    }

    public function tick(): TickResultEnum
    {
        ++$this->ticks;

        if ($this->onTick !== null) {
            ($this->onTick)($this->ticks);
        }

        if ($this->throws) {
            throw new RuntimeException('tick blew up');
        }

        return array_shift($this->results) ?? TickResultEnum::Idle;
    }
}
