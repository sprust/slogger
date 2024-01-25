<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;
use App\Modules\Traces\Enums\TraceTypeEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string        $_id
 * @property string        $serviceId
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
    use HasFactory;

    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updatedAt';

    protected $collection = 'traces';

    protected $casts = [
        'type'     => TraceTypeEnum::class,
        'loggedAt' => 'datetime',
    ];
}
