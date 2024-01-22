<?php

namespace App\Modules\Projects;

use Illuminate\Support\Str;

class ProjectDatabaseCreator
{
    public function makeDatabaseName(int $userId, string $projectName): string
    {
        $projectNameSlug = Str::slug($projectName);

        return "projects-$userId-$projectNameSlug";
    }

    public function create(string $databaseName): void
    {
        // TODO
    }
}
