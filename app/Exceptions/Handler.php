<?php

namespace App\Exceptions;

use App\Modules\Dashboard\Domain\Exceptions\DatabaseStatCacheNotFoundException;
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

            return null;
        });
    }
}
