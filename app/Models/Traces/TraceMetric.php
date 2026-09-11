<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;
use Illuminate\Support\Carbon;

/**
 * @property int    $sid
 * @property string $tp
 * @property Carbon $t
 * @property int    $lc
 * @property int    $bc
 * @property int    $sc
 */
class TraceMetric extends AbstractTraceModel
{
    public const UPDATED_AT = null;
    public const CREATED_AT = null;

    public function getCollectionName(): string
    {
        return 'traceMetrics';
    }
}
