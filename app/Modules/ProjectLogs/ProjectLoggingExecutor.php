<?php

namespace App\Modules\ProjectLogs;

use App\Modules\Projects\ProjectDatabaseContainer;
use Closure;
use Throwable;

class ProjectLoggingExecutor
{
    /**
     * @param Closure(): mixed $closure
     *
     * @throws Throwable
     */
    public static function exec(string $databaseName, Closure $closure): mixed
    {
        /** @var ProjectDatabaseContainer $databaseContainer */
        $databaseContainer = app(ProjectDatabaseContainer::class);

        $databaseNameBackup = $databaseContainer->getDatabaseName();

        $databaseContainer->setDatabaseName($databaseName);

        $exception = null;

        try {
            $result = $closure();
        } catch (Throwable $exception) {
            $result = null;
        }

        $databaseContainer->setDatabaseName($databaseNameBackup);

        if ($exception) {
            throw $exception;
        }

        return $result;
    }

}
