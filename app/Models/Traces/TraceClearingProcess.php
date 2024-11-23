<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;
use Illuminate\Support\Carbon;

/**
 * @property string      $_id
 * @property int         $settingId
 * @property int         $clearedCount
 * @property string|null $error
 * @property Carbon|null $clearedAt
 * @property Carbon      $createdAt
 * @property Carbon      $updatedAt
 */
class TraceClearingProcess extends AbstractTraceModel
{
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updatedAt';

    protected $casts = [
        'clearedAt' => 'datetime',
    ];

    function getCollectionName(): string
    {
        return 'traceClearingProcesses';
    }
}
