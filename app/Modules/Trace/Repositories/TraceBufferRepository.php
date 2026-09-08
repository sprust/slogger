<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceBuffer;
use App\Models\Traces\TraceInvalidBuffer;
use Illuminate\Support\Carbon;
use SConcur\Bson\UTCDateTime;

readonly class TraceBufferRepository
{
    /**
     * How many documents are waiting to be transported.
     *
     * Estimated rather than counted: this is read on a schedule against a collection that
     * can hold millions, and the answer is compared to a threshold in the thousands.
     * `estimatedDocumentCount` reads the collection's own metadata and is O(1);
     * `countDocuments` would scan for a precision nobody here can use.
     */
    public function count(): int
    {
        return TraceBuffer::sconcur()->estimatedDocumentCount();
    }

    /**
     * How many documents were moved to the invalid buffer after the given moment.
     *
     * Counted, not estimated: the question is about a slice, and the slice is normally
     * empty. The `iat` index the TTL rides on is what makes it cheap.
     */
    public function countInvalidBetween(Carbon $since, Carbon $until): int
    {
        return TraceInvalidBuffer::sconcur()->countDocuments([
            'iat' => [
                '$gt'  => new UTCDateTime($since),
                '$lte' => new UTCDateTime($until),
            ],
        ]);
    }
}
