<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Actions\Queries;

use App\Modules\Notification\Domain\Services\Types\NotificationChannelTypeRegistry;
use App\Modules\Notification\Entities\ChannelTypeObject;

readonly class FindChannelTypesAction
{
    public function __construct(
        private NotificationChannelTypeRegistry $types
    ) {
    }

    /**
     * @return ChannelTypeObject[]
     */
    public function handle(): array
    {
        return $this->types->describeAll();
    }
}
