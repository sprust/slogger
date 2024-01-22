<?php

namespace App\Modules\Projects;

class ProjectDatabaseContainer
{
    private ?string $databaseName = null;

    public function getDatabaseName(): ?string
    {
        return $this->databaseName;
    }

    public function setDatabaseName(?string $databaseName): void
    {
        $this->databaseName = $databaseName;
    }
}
