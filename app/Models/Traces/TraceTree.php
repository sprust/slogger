<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;

/**
 * @property string      $_id
 * @property string      $traceId
 * @property string|null $parentTraceId
 */
class TraceTree extends AbstractTraceModel
{
    public const UPDATED_AT = null;

    protected $collection = 'traceTreesView';
}
