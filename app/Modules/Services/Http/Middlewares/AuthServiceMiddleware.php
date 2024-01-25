<?php

namespace App\Modules\Services\Http\Middlewares;

use App\Modules\Services\Http\RequestServiceContainer;
use App\Modules\Services\Repository\ServicesRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class AuthServiceMiddleware
{
    public function __construct(
        private ServicesRepository $servicesRepository,
        private RequestServiceContainer $requestServiceContainer
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token   = $request->bearerToken();

        if (!$token) {
            abort(401);
        }

        $service = $this->servicesRepository->findByToken(
            $token
        );

        if (!$service) {
            abort(401);
        }

        $this->requestServiceContainer->setService($service);

        return $next($request);
    }
}
