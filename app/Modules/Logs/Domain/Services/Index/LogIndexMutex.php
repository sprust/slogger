<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Index;

use App\Modules\Common\Domain\Services\Mutex\AbstractMutex;

readonly class LogIndexMutex extends AbstractMutex
{
    public function __construct(
        private string $fileId,
        private int $waitForBlockSec = 20
    ) {
    }

    public function getKey(): string
    {
        return "logs-index:$this->fileId";
    }

    public function getMaxLockSec(): int
    {
        return 300;
    }

    public function getWaitForBlockSec(): int
    {
        return $this->waitForBlockSec;
    }
}
