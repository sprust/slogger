<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Http\Resources;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A JSON body sent in chunks, as they are produced.
 *
 * Built from an iterable of chunks rather than from a callback that prints: under SConcur
 * that is the one shape the HTTP bridge streams straight from the request coroutine, a chunk
 * leaving memory once it is sent. A printing callback is run to its end into a temporary
 * file before the first byte goes out, and inside an output buffer that belongs to the
 * process — a callback that waits on a query there can have its output mixed with a
 * neighbour's. Outside SConcur, Symfony prints the same chunks one by one.
 *
 * X-Accel-Buffering keeps nginx from spooling the stream to its own disk when the client
 * reads slower than the chunks arrive.
 */
abstract class AbstractStreamedApiResource extends StreamedResponse
{
    /**
     * @param iterable<string> $chunks
     */
    public function __construct(iterable $chunks)
    {
        parent::__construct(
            callbackOrChunks: $chunks,
            status: Response::HTTP_OK,
            headers: [
                'Content-Type'      => 'application/json',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }
}
