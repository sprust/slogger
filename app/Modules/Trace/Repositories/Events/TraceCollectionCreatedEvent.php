<?php

namespace App\Modules\Trace\Repositories\Events;

readonly class TraceCollectionCreatedEvent
{
    public function __construct(public string $collectionName)
    {
    }
}
