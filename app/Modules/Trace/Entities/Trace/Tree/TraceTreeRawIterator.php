<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Tree;

use Closure;
use Iterator;

/**
 * @implements Iterator<int, TraceTreeRawObject>
 */
readonly class TraceTreeRawIterator implements Iterator
{
    /**
     * @param Closure(mixed): TraceTreeRawObject $transport
     */
    public function __construct(
        private Closure $transport,
        private Iterator $iterator,
    ) {
    }

    public function current(): ?TraceTreeRawObject
    {
        $current = $this->iterator->current();

        if (!$current) {
            return null;
        }

        return ($this->transport)($current);
    }

    public function next(): void
    {
        $this->iterator->next();
    }

    public function key(): mixed
    {
        return $this->iterator->key();
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    public function rewind(): void
    {
        $this->iterator->rewind();
    }
}
