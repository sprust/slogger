<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Mutations;

use App\Modules\Trace\Domain\Entities\Objects\Data\TraceDataItemObject;

interface SyncTraceDataActionInterface
{
    /**
     * @return TraceDataItemObject[]
     */
    public function handle(array $data): array;
}
