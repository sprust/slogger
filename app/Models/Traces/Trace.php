<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;
use App\Models\Services\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string            $_id
 * @property string            $sid - serviceId
 * @property string            $tid - traceId
 * @property string|null       $ptid - parentTraceId
 * @property string            $tp - type
 * @property string            $st - status
 * @property array             $tgs - tags
 * @property array             $dt - data
 * @property array             $dtkv - data as key: value
 * @property float|null        $dur - duration
 * @property float|null        $mem - memory
 * @property float|null        $cpu - cpu
 * @property bool              $hpr - hasProfiling
 * @property array             $pr - profiling
 * @property Carbon            $lat - loggedAt
 * @property array             $tss - timestamps
 * @property bool              $cl - cleared
 * @property Carbon            $cat - createdAt
 * @property Carbon            $uat - updatedAt
 * @property-read Service|null $service
 */
class Trace extends AbstractTraceModel
{
    use HasFactory;

    public const CREATED_AT = 'cat';
    public const UPDATED_AT = 'uat';

    protected $collection = 'traces';

    protected $casts = [
        'hpr' => 'boolean',
        'lat' => 'datetime',
        'cl'  => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'sid', 'id');
    }
}
