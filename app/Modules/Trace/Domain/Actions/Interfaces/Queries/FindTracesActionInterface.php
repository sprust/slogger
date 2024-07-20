<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Objects\TraceItemObjects;
use App\Modules\Trace\Domain\Entities\Parameters\TraceFindParameters;
use App\Modules\Trace\Domain\Exceptions\TraceIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceIndexNotInitException;

interface FindTracesActionInterface
{
    /**
     * @throws TraceIndexNotInitException
     * @throws TraceIndexInProcessException
     */
    public function handle(TraceFindParameters $parameters): TraceItemObjects;
}
