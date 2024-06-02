<?php

namespace App\Modules\Trace\Domain\Entities\Transports;

use App\Modules\Trace\Domain\Entities\Objects\Profiling\ProfilingItemDataObject;
use App\Modules\Trace\Domain\Entities\Objects\Profiling\ProfilingItemObject;
use App\Modules\Trace\Domain\Entities\Objects\Profiling\ProfilingObject;
use Illuminate\Support\Str;

class TraceProfilingTransport
{
    public static function toObject(array $profiling): ProfilingObject
    {
        $objects = [];

        foreach ($profiling['items'] as $item) {
            $objects[] = new ProfilingItemObject(
                id: Str::uuid()->toString(),
                calling: $item['calling'],
                callable: $item['callable'],
                data: array_map(
                    fn(array $itemData) => new ProfilingItemDataObject(
                        name: $itemData['name'],
                        value: $itemData['value']
                    ),
                    $item['data']
                ),
            );
        }

        return new ProfilingObject(
            mainCaller: $profiling['mainCaller'],
            items: $objects
        );
    }
}
