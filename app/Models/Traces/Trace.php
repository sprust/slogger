<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string      $_id
 * @property string      $serviceId
 * @property string      $traceId
 * @property string|null $parentTraceId
 * @property string      $type
 * @property array       $tags
 * @property array       $data
 * @property Carbon      $loggedAt
 * @property Carbon      $createdAt
 * @property Carbon      $updatedAt
 */
class Trace extends AbstractTraceModel
{
    use HasFactory;

    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updatedAt';

    protected $collection = 'traces';

    protected $casts = [
        'loggedAt' => 'datetime',
    ];
}
