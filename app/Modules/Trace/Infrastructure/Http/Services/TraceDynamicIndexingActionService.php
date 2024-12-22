<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Services;

use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;

class TraceDynamicIndexingActionService
{
    private int $indexCreateTimeoutInSeconds;

    public function __construct()
    {
        $this->indexCreateTimeoutInSeconds = 10;
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
            } catch (TraceDynamicIndexInProcessException $exception) {
                abort_if(
                    boolean: (time() - $start) > $this->indexCreateTimeoutInSeconds,
                    code: 400,
                    message: 'Indexing in progress. Try again or later.'
                );

                sleep(1);

                continue;
            } catch (TraceDynamicIndexErrorException $exception) {
                abort(
                    code: 500,
                    message: $exception->getMessage()
                );
            }

            break;
        }

        return $result;
    }
}
