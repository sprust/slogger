<?php

namespace App\Modules\Projects;

use App\Modules\ProjectLogs\ProjectLoggingExecutor;
use App\Modules\ProjectLogs\ProjectLogsRepository;
use Illuminate\Support\Str;

readonly class ProjectDatabaseCreator
{
    public function __construct(private ProjectLogsRepository $logsRepository)
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
        ProjectLoggingExecutor::exec(
            $databaseName,
            function () {
                $this->logsRepository->create();
            }
        );
    }
}
