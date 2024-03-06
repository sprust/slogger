<?php

namespace App\Modules\Dashboard\Dto\Objects\Database;

class DatabaseObjects
{
    /** @var DatabaseObject[] */
    private array $items = [];

    public function add(DatabaseObject $database): static
    {
        $this->items[] = $database;

        return $this;
    }

    /**
     * @return DatabaseObject[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
