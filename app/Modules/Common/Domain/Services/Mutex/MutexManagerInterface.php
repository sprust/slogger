<?php

declare(strict_types=1);

namespace App\Modules\Common\Domain\Services\Mutex;

interface MutexManagerInterface
{
    public function lock(AbstractMutex $mutex): void;

    public function unlock(AbstractMutex $mutex): void;
}
