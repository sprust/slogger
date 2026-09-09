<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Queries;

use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;
use App\Modules\Watcher\Entities\WatcherTypeObject;

readonly class FindWatcherTypesAction
{
    public function __construct(
        private WatcherTypeRegistry $types
    ) {
    }

    /**
     * @return WatcherTypeObject[]
     */
    public function handle(): array
    {
        return $this->types->describeAll();
    }
}
