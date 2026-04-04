<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use Illuminate\Support\Carbon;

/**
 * @property string                        $_id
 * @property string                        $rootTraceId
 * @property string                        $version
 * @property TraceTreeCacheStateStatusEnum $status
 * @property int                           $count
 * @property string|null                   $error
 * @property Carbon|null                   $startedAt
 * @property Carbon|null                   $finishedAt
 * @property Carbon                        $createdAt
 * @property Carbon                        $updatedAt
 */
class TraceTreeCacheState extends AbstractTraceModel
{
    public const string CREATED_AT = 'createdAt';
    public const string UPDATED_AT = 'updatedAt';

    protected $casts = [
        'status'     => TraceTreeCacheStateStatusEnum::class,
        'count'      => 'int',
        'startedAt'  => 'datetime',
        'finishedAt' => 'datetime',
    ];

    public function getCollectionName(): string
    {
        return 'traceTreeCacheStates';
    }
}
