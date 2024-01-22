<?php

namespace App\Modules\Projects\Repository;

use App\Models\Projects\Project;
use App\Modules\Projects\ProjectDatabaseCreator;
use App\Modules\Projects\Repository\Exceptions\ProjectAlreadyExistsException;
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
     * @throws ProjectAlreadyExistsException
     */
    public function create(ProjectsCreateParameters $parameters): Project
    {
        $name = Str::slug($parameters->name);

        $exists = Project::query()
            ->where([
                    'user_id' => $parameters->userId,
                    'name'    => $name,
                ]
            )
            ->exists();

        if ($exists) {
            throw new ProjectAlreadyExistsException($parameters->name);
        }

        $newProject = new Project();

        $newProject->user_id       = $parameters->userId;
        $newProject->name          = $name;
        $newProject->database_name = $this->projectDatabaseCreator->makeDatabaseName(
            userId: $parameters->userId,
            projectName: $parameters->name
        );

        DB::beginTransaction();

        try {
            $newProject->saveOrFail();

            $this->projectDatabaseCreator->create(
                $newProject->database_name
            );

            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        return $newProject;
    }
}
