<?php

declare(strict_types=1);

namespace App\Modules\Common\Domain\Services\Mutex;

use Closure;

abstract readonly class AbstractMutex
{
    abstract public function getKey(): string;

    public function getMaxLockSec(): int
    {
        return 10;
    }

    public function getWaitForBlockSec(): int
    {
        return 15;
    }

    public function getAfterLockHandler(): ?Closure
    {
        return null;
    }

    public function getBeforeReleaseHandler(): ?Closure
    {
        return null;
    }
}
