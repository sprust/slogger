<?php

namespace App\Modules\Services\Http\Middlewares;

use App\Modules\Services\Http\RequestServiceContainer;
use App\Modules\Services\Services\ServicesService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class AuthServiceMiddleware
{
    public function __construct(
        private ServicesService $servicesService,
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
        $token = $request->bearerToken();

        if (!$token) {
            abort(401);
        }

        $service = $this->servicesService->findByToken($token);

        if (!$service) {
            abort(401);
        }

        $this->requestServiceContainer->setService($service);

        return $next($request);
    }
}