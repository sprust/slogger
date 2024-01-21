<?php

namespace App\Modules\Projects\Repository;

use App\Models\Projects\Project;
use App\Modules\Projects\Repository\Parameters\ProjectsCreateParameters;
use Illuminate\Support\Str;

class ProjectsRepository
{
    public function create(ProjectsCreateParameters $parameters): Project
    {
        $newProject = new Project();

        $newProject->user_id = $parameters->userId;
        $newProject->name    = Str::slug($parameters->name);

        $newProject->saveOrFail();

        return $newProject;
    }
}
