<?php

namespace App\Models\Traces;

use App\Models\AbstractTraceModel;
use Illuminate\Support\Carbon;

/**
 * Where the receiver puts a buffer document it could not act on, with the reason beside
 * it. Written there by Go; read here only to notice that it grew.
 *
 * @property Carbon $iat when the document was moved here
 * @property string $rsn why
 */
class TraceInvalidBuffer extends AbstractTraceModel
{
    public function getCollectionName(): string
    {
        return 'invalidBuffer';
    }
}
