<?php

namespace App\Modules\ProjectLogs;

use App\Modules\ProjectLogs\RawLogs\ProjectRawLogsMigration;
use Throwable;

readonly class ProjectLogsMigration
{
    public function __construct(
        private ProjectRawLogsMigration $rawLogsMigration
    ) {
    }

    /**
     * @throws Throwable
     */
    public function create(): void
    {
        $this->rawLogsMigration->up();
    }

    /**
     * @throws Throwable
     */
    public function delete(): void
    {
        $this->rawLogsMigration->down();
    }
}
