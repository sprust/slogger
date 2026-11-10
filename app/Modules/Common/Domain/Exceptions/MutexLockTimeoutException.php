<?php

declare(strict_types=1);

namespace App\Modules\Common\Domain\Exceptions;

class MutexLockTimeoutException extends AbstractCommonException
{
    public function __construct(string $key)
    {
        parent::__construct("Mutex [$key] was not acquired in time");
    }
}
