<?php

namespace App\Models\Logs;

use App\Models\AbstractProjectModel;

/**
 * @property string $trackId
 * @property string|null $parentTrackId
 * @property array $data
 */
class RawLog extends AbstractProjectModel
{
    static protected function getCollectionName(): string
    {
        return 'raw';
    }
}
