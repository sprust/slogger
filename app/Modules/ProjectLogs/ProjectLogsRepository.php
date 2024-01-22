<?php

namespace App\Modules\ProjectLogs;

use App\Modules\ProjectLogs\ProjectLogsRaw\ProjectLogsRawMigration;
use Throwable;

readonly class ProjectLogsRepository
{
    public function __construct(
        private ProjectLogsRawMigration $logsRawMigration
    ) {
    }

    /**
     * @throws Throwable
     */
    public function create(): void
    {
        $this->logsRawMigration->up();
    }
}
