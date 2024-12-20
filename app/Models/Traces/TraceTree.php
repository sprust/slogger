<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;

/**
 * @property string      $_id
 * @property string      $tid
 * @property string|null $ptid
 * @property string      $__cn // collection name
 */
class TraceTree extends AbstractTraceModel
{
    public const UPDATED_AT = null;

    protected $connection = 'mongodb.tracesPeriodic';

    function getCollectionName(): string
    {
        return '_traceTreesView';
    }
}
