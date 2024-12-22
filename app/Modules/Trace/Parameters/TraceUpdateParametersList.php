<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters;

class TraceUpdateParametersList
{
    /** @var TraceUpdateParameters[] */
    private array $items = [];

    public function add(TraceUpdateParameters $parameters): static
    {
        $this->items[] = $parameters;

        return $this;
    }

    /**
     * @return TraceUpdateParameters[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }
}
