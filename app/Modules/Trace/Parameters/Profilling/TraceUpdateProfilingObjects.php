<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters\Profilling;

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

    /**
     * @return TraceUpdateProfilingObject[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
