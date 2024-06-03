<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;
use App\Models\Services\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string       $_id
 * @property string       $serviceId
 * @property string       $traceId
 * @property string|null  $parentTraceId
 * @property string       $type
 * @property string       $status
 * @property array        $tags
 * @property array        $data
 * @property float|null   $duration
 * @property float|null   $memory
 * @property float|null   $cpu
 * @property bool         $hasProfiling
 * @property array        $profiling
 * @property Carbon       $loggedAt
 * @property array        $timestamps
 * @property Carbon       $createdAt
 * @property Carbon       $updatedAt
 * @property-read Service $service
 */
class Trace extends AbstractTraceModel
{
    use HasFactory;

    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updatedAt';

    protected $collection = 'traces';

    protected $casts = [
        'hasProfiling' => 'boolean',
        'loggedAt'     => 'datetime',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'serviceId', 'id');
    }
}
