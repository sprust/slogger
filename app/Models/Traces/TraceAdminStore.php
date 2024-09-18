<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;
use Illuminate\Support\Carbon;

/**
 * @property string      $_id
 * @property string      $title
 * @property int         $storeVersion
 * @property string      $storeDataHash
 * @property string      $storeData
 * @property int         $creatorId
 * @property Carbon|null $usedAt
 * @property Carbon      $createdAt
 */
class TraceAdminStore extends AbstractTraceModel
{
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = null;

    protected $collection = 'traceAdminStores';

    protected $casts = [
        'usedAt' => 'datetime',
    ];
}
