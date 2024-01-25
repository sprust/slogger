<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;
use App\Modules\Traces\TraceTypeEnum;
use Carbon\Carbon;

/**
 * @property string        $_id
 * @property string        $service
 * @property string        $traceId
 * @property string|null   $parentTraceId
 * @property TraceTypeEnum $type
 * @property array         $tags
 * @property array         $data
 * @property Carbon        $loggedAt
 * @property Carbon        $createdAt
 * @property Carbon        $updatedAt
 */
class Trace extends AbstractTraceModel
{
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updatedAt';

    protected $collection = 'traces';

    protected $casts = [
        'type'     => TraceTypeEnum::class,
        'loggedAt' => 'datetime',
    ];
}
