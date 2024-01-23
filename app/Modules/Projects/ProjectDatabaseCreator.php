<?php

namespace App\Modules\Projects;

use App\Modules\ProjectLogs\ProjectLoggingExecutor;
use App\Modules\ProjectLogs\ProjectLogsMigration;
use Illuminate\Support\Str;

readonly class ProjectDatabaseCreator
{
    public function __construct(private ProjectLogsMigration $logsRepository)
    {
    }

    public function makeDatabaseName(int $userId, string $projectName): string
    {
        // not dots!!!

        $projectNameSlug = Str::slug($projectName, '-');

        return "projects-$userId-$projectNameSlug";
    }

    public function create(string $databaseName): void
    {
        ProjectLoggingExecutor::usingDatabase(
            $databaseName,
            function () {
                $this->logsRepository->create();
            }
        );
    }
}
