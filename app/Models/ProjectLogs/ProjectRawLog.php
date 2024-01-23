<?php

namespace App\Models\ProjectLogs;

use App\Models\AbstractProjectLogsModel;
use App\Modules\ProjectLogs\ProjectLogTypeEnum;
use App\Modules\ProjectLogs\RawLogs\ProjectRawLogsMigration;
use Carbon\Carbon;

/**
 * @see ProjectRawLogsMigration::up() - migration
 *
 * @property string             $_id
 * @property string             $service
 * @property string             $trackId
 * @property string|null        $parentTrackId
 * @property ProjectLogTypeEnum $type
 * @property array              $tags
 * @property array              $data
 * @property Carbon             $loggedAt
 * @property Carbon             $createdAt
 */
class ProjectRawLog extends AbstractProjectLogsModel
{
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = null;

    protected $casts = [
        'type'     => ProjectLogTypeEnum::class,
        'loggedAt' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    static protected function getCollectionName(): string
    {
        return 'raw';
    }
}
