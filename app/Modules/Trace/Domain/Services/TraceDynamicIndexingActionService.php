<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Services;

use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;

class TraceDynamicIndexingActionService
{
    private int $indexCreateTimeoutInSeconds;

    public function __construct()
    {
        $this->indexCreateTimeoutInSeconds = 120;
    }

    /**
     * @throws TraceDynamicIndexNotInitException
     * @throws TraceDynamicIndexInProcessException
     * @throws TraceDynamicIndexErrorException
     */
    public function handle(callable $action): mixed
    {
        $start = time();

        while (true) {
            try {
                $result = $action();
            } catch (TraceDynamicIndexInProcessException) {
                if (time() - $start > $this->indexCreateTimeoutInSeconds) {
                    throw new TraceDynamicIndexNotInitException();
                }

                sleep(5);

                continue;
            }

            break;
        }

        return $result;
    }
}
