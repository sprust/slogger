<?php

namespace App\Modules\Projects\Repository;

use App\Models\Projects\Project;
use App\Modules\Projects\ProjectDatabaseCreator;
use App\Modules\Projects\Repository\Parameters\ProjectsCreateParameters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

readonly class ProjectsRepository
{
    public function __construct(
        private ProjectDatabaseCreator $projectDatabaseCreator
    ) {
    }

    /**
     * @throws Throwable
     */
    public function create(ProjectsCreateParameters $parameters): Project
    {
        $newProject = new Project();

        $newProject->user_id  = $parameters->userId;
        $newProject->name     = Str::slug($parameters->name);
        $newProject->database_name = $this->projectDatabaseCreator->makeDatabaseName(
            userId: $parameters->userId,
            projectName: $parameters->name
        );

        DB::beginTransaction();

        try {
            $newProject->saveOrFail();

            $this->projectDatabaseCreator->create($newProject->database_name);

            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        return $newProject;
    }
}
