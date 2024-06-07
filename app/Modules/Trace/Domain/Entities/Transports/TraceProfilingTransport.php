<?php

namespace App\Modules\Trace\Domain\Entities\Transports;

use App\Modules\Trace\Domain\Entities\Objects\Profiling\ProfilingItemDataObject;
use App\Modules\Trace\Domain\Entities\Objects\Profiling\ProfilingItemObject;
use App\Modules\Trace\Domain\Entities\Objects\Profiling\ProfilingObject;
use App\Modules\Trace\Repositories\Dto\Profiling\TraceProfilingDataDto;
use App\Modules\Trace\Repositories\Dto\Profiling\TraceProfilingDto;
use Illuminate\Support\Str;

class TraceProfilingTransport
{
    public static function toObject(TraceProfilingDto $profilingDto): ProfilingObject
    {
        $objects = [];

        foreach ($profilingDto->items as $item) {
            $objects[] = new ProfilingItemObject(
                id: Str::uuid()->toString(),
                calling: $item->calling,
                callable: $item->callable,
                data: array_map(
                    fn(TraceProfilingDataDto $itemData) => new ProfilingItemDataObject(
                        name: $itemData->name,
                        value: $itemData->value
                    ),
                    $item->data
                ),
            );
        }

        return new ProfilingObject(
            mainCaller: $profilingDto->mainCaller,
            items: $objects
        );
    }
}
