<?php

declare(strict_types=1);

namespace App\Modules\Common\Domain\Services\Mutex;

use App\Modules\Common\Domain\Exceptions\MutexLockTimeoutException;

interface MutexManagerInterface
{
    /**
     * @throws MutexLockTimeoutException
     */
    public function lock(AbstractMutex $mutex): void;

    public function unlock(AbstractMutex $mutex): void;
}
