<?php

namespace App\Console\Commands\Migrate;

use App\Models\Projects\Project;
use App\Modules\ProjectLogs\ProjectLoggingExecutor;
use App\Modules\ProjectLogs\ProjectLogsRepository;
use Illuminate\Database\Console\Migrations\FreshCommand;

class MigrateFreshCommand extends FreshCommand
{
    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return 1;
        }

        $this->components->info('Dropping all project tables');

        $logsRepository = app(ProjectLogsRepository::class);

        foreach (Project::query()->pluck('database_name') as $databaseName) {
            $this->components->task(
                "Drop $databaseName",
                function () use ($databaseName, $logsRepository) {
                    ProjectLoggingExecutor::usingDatabase(
                        $databaseName,
                        function () use ($logsRepository) {
                            $logsRepository->delete();
                        }
                    );

                    return true;
                }
            );
        }

        return parent::handle();
    }
}
