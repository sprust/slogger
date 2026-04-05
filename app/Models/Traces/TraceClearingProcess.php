<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;
use Illuminate\Support\Carbon;

/**
 * @property string      $_id
 * @property int         $clearedCollectionsCount
 * @property int         $clearedTracesCount
 * @property string|null $error
 * @property string|null $errorTrace
 * @property Carbon|null $clearedAt
 * @property Carbon      $createdAt
 * @property Carbon      $updatedAt
 */
class TraceClearingProcess extends AbstractTraceModel
{
    public const string CREATED_AT = 'createdAt';
    public const string UPDATED_AT = 'updatedAt';

    protected $casts = [
        'clearedAt' => 'datetime',
    ];

    public function getCollectionName(): string
    {
        return 'traceClearingProcesses';
    }
}
