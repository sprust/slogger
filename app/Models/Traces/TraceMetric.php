<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;
use Illuminate\Support\Carbon;

/**
 * @property string $_id
 * @property int    $serviceId
 * @property string $type
 * @property string $status
 * @property Carbon $timestamp
 * @property int    $count
 */
class TraceMetric extends AbstractTraceModel
{
    protected $collection = 'traceMetrics';

    public $timestamps = null;

    protected $casts = [
        'timestamp' => 'datetime',
    ];
}
