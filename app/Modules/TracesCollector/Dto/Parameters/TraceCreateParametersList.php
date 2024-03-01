<?php

namespace App\Modules\TracesCollector\Dto\Parameters;

class TraceCreateParametersList
{
    /** @var TraceCreateParameters[] */
    private array $items = [];

    public function add(TraceCreateParameters $parameters): static
    {
        $this->items[] = $parameters;

        return $this;
    }

    /**
     * @return TraceCreateParameters[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
