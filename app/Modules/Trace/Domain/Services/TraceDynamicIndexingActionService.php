<?php

namespace App\Modules\Trace\Domain\Services;

use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;

class TraceDynamicIndexingActionService
{
    private int $indexCreateTimeoutInSeconds;

    public function __construct()
    {
        $this->indexCreateTimeoutInSeconds = 60;
    }

    /**
     * @throws TraceDynamicIndexNotInitException
     * @throws TraceDynamicIndexInProcessException
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
