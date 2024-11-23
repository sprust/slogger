<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;

/**
 * @property string      $_id
 * @property string      $tid
 * @property string|null $ptid
 */
class TraceTree extends AbstractTraceModel
{
    public const UPDATED_AT = null;

    function getCollectionName(): string
    {
        return 'traceTreesView';
    }
}
