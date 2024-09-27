<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use Illuminate\Support\Carbon;

interface FindTraceIdsActionInterface
{
    /**
     * @return string[]
     *
     * @throws TraceDynamicIndexInProcessException
     * @throws TraceDynamicIndexNotInitException
     * @throws TraceDynamicIndexErrorException
     * */
    public function handle(
        int $limit,
        Carbon $loggedAtTo,
        ?string $type = null,
        ?array $excludedTypes = null
    ): array;
}
