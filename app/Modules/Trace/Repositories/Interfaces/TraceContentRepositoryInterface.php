<?php

namespace App\Modules\Trace\Repositories\Interfaces;

use App\Modules\Trace\Repositories\Dto\Data\TraceDataFilterDto;
use App\Modules\Trace\Repositories\Dto\TraceStringFieldDto;
use Illuminate\Support\Carbon;

interface TraceContentRepositoryInterface
{
    /**
     * @return TraceStringFieldDto[]
     */
    public function findTypes(
        array $serviceIds = [],
        ?string $text = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        ?TraceDataFilterDto $data = null,
        ?bool $hasProfiling = null,
    ): array;

    /**
     * @return TraceStringFieldDto[]
     */
    public function findTags(
        array $serviceIds = [],
        ?string $text = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        ?TraceDataFilterDto $data = null,
        ?bool $hasProfiling = null,
    ): array;

    /**
     * @return TraceStringFieldDto[]
     */
    public function findStatuses(
        array $serviceIds = [],
        ?string $text = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        array $tags = [],
        ?TraceDataFilterDto $data = null,
        ?bool $hasProfiling = null,
    ): array;
}
