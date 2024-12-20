<?php

namespace App\Modules\Trace\Contracts\Actions\Queries;

use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Entities\Trace\TraceCollectionNameObjects;
use Illuminate\Support\Carbon;

interface FindTraceIdsActionInterface
{
    /**
     * @throws TraceDynamicIndexInProcessException
     * @throws TraceDynamicIndexNotInitException
     * @throws TraceDynamicIndexErrorException
     * */
    public function handle(
        int $limit,
        Carbon $loggedAtTo,
        ?string $type = null,
        ?array $excludedTypes = null,
        ?bool $noCleared = null
    ): TraceCollectionNameObjects;
}
