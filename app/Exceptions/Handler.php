<?php

namespace App\Exceptions;

use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
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

            return true;
        });

        $this->renderable(function (Throwable $exception) {
            if ($exception instanceof TraceDynamicIndexInProcessException) {
                return response()->json(
                    data: [
                        'error' => 'Trace dynamic index is in process. Try again later.',
                    ],
                    status: Response::HTTP_PRECONDITION_FAILED
                );
            }

            return null;
        });
    }
}
