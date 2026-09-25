<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Services;

use Illuminate\Contracts\Cache\Lock;

readonly class MutexHolder
{
    public function __construct(
        public Lock $lock,
        public int $level,
        public int $fiberId
    ) {
    }
}
