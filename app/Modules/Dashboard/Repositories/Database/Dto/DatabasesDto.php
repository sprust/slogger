<?php

namespace App\Modules\Dashboard\Repositories\Database\Dto;

class DatabasesDto
{
    /** @var DatabaseDto[] */
    private array $items = [];

    public function add(DatabaseDto $database): static
    {
        $this->items[] = $database;

        return $this;
    }

    /**
     * @return DatabaseDto[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
