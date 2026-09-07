<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;

/**
 * The intake buffer, written and drained by the Go receiver.
 *
 * Nothing here writes to it. The panel reads how full it is, which is a symptom: a buffer
 * that stops draining means the transporter is behind, and traces are not reaching the
 * shards.
 */
class TraceBuffer extends AbstractTraceModel
{
    public function getCollectionName(): string
    {
        return 'buffer';
    }
}
