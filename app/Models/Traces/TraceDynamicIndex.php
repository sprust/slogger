<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;
use Illuminate\Support\Carbon;

/**
 * @property string      $_id
 * @property string      $indexName
 * @property string      $fieldsKey
 * @property Carbon|null $loggedAtFrom
 * @property Carbon|null $loggedAtTo
 * @property array       $fields
 * @property bool        $inProcess
 * @property bool        $created
 * @property string|null $error
 * @property Carbon      $actualUntilAt
 * @property Carbon      $createdAt
 */
class TraceDynamicIndex extends AbstractTraceModel
{
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = null;

    protected $casts = [
        'loggedAtFrom'  => 'datetime',
        'loggedAtTo'    => 'datetime',
        'inProcess'     => 'bool',
        'created'       => 'bool',
        'actualUntilAt' => 'datetime',
    ];

    function getCollectionName(): string
    {
        return 'traceDynamicIndexes';
    }
}
