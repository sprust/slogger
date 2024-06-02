<?php

namespace App\Modules\TraceCollector\Repositories\Interfaces;

use App\Modules\TraceCollector\Repositories\Dto\TraceTreeDto;
use Illuminate\Support\Carbon;

interface TraceTreeRepositoryInterface
{
    /**
     * @param TraceTreeDto[] $parametersList
     */
    public function insertMany(array $parametersList): void;

    public function deleteMany(Carbon $to): void;
}
