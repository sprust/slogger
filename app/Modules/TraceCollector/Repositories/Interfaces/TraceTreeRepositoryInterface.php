<?php

namespace App\Modules\TraceCollector\Repositories\Interfaces;

use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceTreeDeleteManyParameters;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceTreeInsertParameters;

interface TraceTreeRepositoryInterface
{
    /**
     * @param TraceTreeInsertParameters[] $parametersList
     */
    public function insertMany(array $parametersList): void;

    public function deleteMany(TraceTreeDeleteManyParameters $parameters): void;
}
