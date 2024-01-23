<?php

namespace App\Modules\ProjectLogs\ProjectLogsRaw\Parameters;

class CreateProjectRawLogParametersList
{
    /** @var CreateProjectRawLogParameters[] */
    private array $items = [];

    public function add(CreateProjectRawLogParameters $parameters): static
    {
        $this->items[] = $parameters;

        return $this;
    }

    /**
     * @return CreateProjectRawLogParameters[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
