<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Repositories\Services\ClickhouseDataPathTypes;
use App\Services\Clickhouse\ClickhouseQueryException;

/**
 * Reads again which paths of the trace data hold arrays of objects, for the data filters.
 */
readonly class RefreshTraceDataPathTypesAction
{
    public function __construct(
        private ClickhouseDataPathTypes $pathTypes,
    ) {
    }

    /**
     * @return int how many array paths were found
     *
     * @throws ClickhouseQueryException
     */
    public function handle(): int
    {
        return count($this->pathTypes->refresh());
    }
}
