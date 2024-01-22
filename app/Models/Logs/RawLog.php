<?php

namespace App\Models\Logs;

use App\Models\AbstractProjectLogsModel;
use App\Modules\ProjectLogs\LogTypeEnum;
use App\Modules\ProjectLogs\ProjectLogsRaw\ProjectLogsRawMigration;
use Carbon\Carbon;

/**
 * @see ProjectLogsRawMigration::up() - migration
 *
 * @property string      $_id
 * @property string      $service
 * @property string      $trackId
 * @property string|null $parentTrackId
 * @property LogTypeEnum $type
 * @property array       $tags
 * @property array       $data
 * @property Carbon      $loggedAt
 * @property Carbon      $createdAt
 */
class RawLog extends AbstractProjectLogsModel
{
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = null;

    protected $casts = [
        'type'     => LogTypeEnum::class,
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
