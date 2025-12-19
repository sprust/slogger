<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Http\Resources;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class AbstractStreamedApiResource extends StreamedResponse
{
    public function __construct(callable $callback)
    {
        parent::__construct(
            callbackOrChunks: $callback,
            status: Response::HTTP_OK,
            headers: [
                'Content-Type' => 'application/json',
            ]
        );
    }
}
