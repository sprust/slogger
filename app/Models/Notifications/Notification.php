<?php

namespace App\Models\Notifications;

use App\Models\AbstractTraceModel;
use Illuminate\Support\Carbon;

/**
 * @property string      $_id
 * @property int         $channelId
 * @property int|null    $watcherId
 * @property string|null $incidentId
 * @property string      $kind
 * @property string      $text
 * @property Carbon|null $sentAt
 * @property string|null $error
 * @property Carbon      $createdAt
 */
class Notification extends AbstractTraceModel
{
    public const UPDATED_AT = null;
    public const CREATED_AT = null;

    public function getCollectionName(): string
    {
        return 'notifications';
    }
}
