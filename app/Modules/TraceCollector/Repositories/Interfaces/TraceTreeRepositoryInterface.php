<?php

namespace App\Modules\TraceCollector\Repositories\Interfaces;

use App\Modules\TraceCollector\Repositories\Dto\TraceTreeCreateParametersDto;
use Illuminate\Support\Carbon;

interface TraceTreeRepositoryInterface
{
    /**
     * @param TraceTreeCreateParametersDto[] $parametersList
     */
    public function insertMany(array $parametersList): void;

    public function deleteMany(Carbon $to): void;
}
