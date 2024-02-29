<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;
use Carbon\Carbon;

/**
 * @property string       $_id
 * @property string       $traceId
 * @property string|null  $parentTraceId
 * @property Carbon       $createdAt
 */
class TraceTree extends AbstractTraceModel
{
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = null;

    protected $collection = 'traceTrees';
}
