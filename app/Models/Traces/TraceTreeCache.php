<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;
use Illuminate\Support\Carbon;

/**
 * @property  string     $_id
 * @property  string     $parentTraceId
 * @property  string     $traceId
 * @property  int|null   $serviceId
 * @property  string     $type
 * @property  string     $status
 * @property  array      $tags
 * @property  float|null $duration
 * @property  float|null $memory
 * @property  float|null $cpu
 * @property  int|null   $order
 * @property  int|null   $depth
 * @property  Carbon     $loggedAt
 * @property  Carbon     $createdAt
 */
class TraceTreeCache extends AbstractTraceModel
{
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = null;

    function getCollectionName(): string
    {
        return 'traceTreeCache';
    }
}
