<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\WatcherIncidentStatObject;

/**
 * What the header's badge reads.
 *
 * An endpoint of its own rather than the length of the incidents list: the badge is shown
 * on every page, and counting rows the panel does not display would mean fetching them.
 */
class WatcherIncidentStatResource extends AbstractApiResource
{
    private int $opened_count;

    public function __construct(WatcherIncidentStatObject $resource)
    {
        parent::__construct($resource);

        $this->opened_count = $resource->openedCount;
    }
}
