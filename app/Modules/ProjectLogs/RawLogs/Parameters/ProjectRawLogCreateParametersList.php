<?php

namespace App\Modules\ProjectLogs\RawLogs\Parameters;

class ProjectRawLogCreateParametersList
{
    /** @var ProjectRawLogCreateParameters[] */
    private array $items = [];

    public function add(ProjectRawLogCreateParameters $parameters): static
    {
        $this->items[] = $parameters;

        return $this;
    }

    /**
     * @return ProjectRawLogCreateParameters[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
