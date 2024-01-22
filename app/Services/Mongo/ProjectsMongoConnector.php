<?php

namespace App\Services\Mongo;

use App\Modules\Projects\ProjectDatabaseContainer;
use MongoDB\Laravel\Connection;

class ProjectsMongoConnector extends Connection
{
    protected function getDefaultDatabaseName(string $dsn, array $config): string
    {
        return app(ProjectDatabaseContainer::class)->getDatabaseName();
    }
}
