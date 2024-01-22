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
 */
class RawLog extends AbstractProjectLogsModel
{
    protected $casts = [
        'type'     => LogTypeEnum::class,
        'actionAt' => 'timestamp',
    ];

    static protected function getCollectionName(): string
    {
        return 'raw';
    }
}
