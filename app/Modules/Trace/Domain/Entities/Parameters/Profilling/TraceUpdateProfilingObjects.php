<?php

namespace App\Modules\Trace\Domain\Entities\Parameters\Profilling;

class TraceUpdateProfilingObjects
{
    /** @var TraceUpdateProfilingObject[] */
    private array $items = [];

    public function __construct(private readonly string $mainCaller)
    {
    }

    public function getMainCaller(): string
    {
        return $this->mainCaller;
    }

    public function add(TraceUpdateProfilingObject $object): static
    {
        $this->items[] = $object;

        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
