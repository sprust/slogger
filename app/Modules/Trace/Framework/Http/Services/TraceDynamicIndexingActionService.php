<?php

namespace App\Modules\Trace\Framework\Http\Services;

use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;

class TraceDynamicIndexingActionService
{
    private int $indexCreateTimeoutInSeconds;

    public function __construct()
    {
        $this->indexCreateTimeoutInSeconds = 20;
    }

    /**
     * @throws TraceDynamicIndexNotInitException
     */
    public function handle(callable $action): mixed
    {
        $start = time();

        while (true) {
            try {
                $result = $action();
            } catch (TraceDynamicIndexInProcessException) {
                abort_if(
                    boolean: time() - $start > $this->indexCreateTimeoutInSeconds,
                    code: 500,
                    message: "Couldn't init index. Try again."
                );

                sleep(1);

                continue;
            }

            break;
        }

        return $result;
    }
}
