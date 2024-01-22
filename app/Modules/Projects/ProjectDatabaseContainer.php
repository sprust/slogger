<?php

namespace App\Modules\Projects;

use LogicException;

class ProjectDatabaseContainer
{
    private string $databaseName;

    public function getDatabaseName(): string
    {
        if (!isset($this->databaseName)) {
            throw new LogicException("Project database name hasn't injected!");
        }

        return $this->databaseName;
    }

    public function setDatabaseName(string $databaseName): void
    {
        $this->databaseName = $databaseName;
    }
}
