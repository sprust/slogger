<?php

namespace App\Modules\Trace\Repositories\Interfaces;

use App\Modules\Trace\Repositories\Dto\TraceTreeDto;
use Illuminate\Support\Carbon;

interface CollectorTraceTreeRepositoryInterface
{
    /**
     * @param TraceTreeDto[] $parametersList
     */
    public function insertMany(array $parametersList): void;

    public function deleteMany(Carbon $to): void;
}
