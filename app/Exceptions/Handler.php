<?php

namespace App\Exceptions;

use App\Modules\Dashboard\Domain\Exceptions\DatabaseStatCacheNotFoundException;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDynamicIndexAction;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceDynamicIndexResource;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $exception) {
            if ($exception instanceof TraceDynamicIndexInProcessException) {
                return false;
            }

            if ($exception instanceof DatabaseStatCacheNotFoundException) {
                return false;
            }


            return true;
        });

        $this->renderable(function (Throwable $exception) {
            if ($exception instanceof DatabaseStatCacheNotFoundException) {
                return response()->json(
                    data: ['error' => 'No data yet. Please wait for the first cache refresh.'],
                    status: Response::HTTP_SERVICE_UNAVAILABLE
                );
            }


            if ($exception instanceof TraceDynamicIndexInProcessException) {
                $index = app(FindTraceDynamicIndexAction::class)->handle(
                    indexId: $exception->indexId
                );

                return response()->json(
                    data: [
                        'error' => 'Trace dynamic index is in process.',
                        'data'  => ($index === null)
                            ? null
                            : new TraceDynamicIndexResource($index)->toArray(),
                    ],
                    status: Response::HTTP_PRECONDITION_FAILED
                );
            }

            return null;
        });
    }
}
