<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;

/**
 * @property string $_id
 * @property string $k
 */
class TraceData extends AbstractTraceModel
{
    public $timestamps = null;

    protected $collection = 'tracesData';
}
