<?php

namespace App\Modules\Service\Framework\Http\Middlewares;

use App\Modules\Service\Domain\Actions\FindServiceByTokenAction;
use App\Modules\Service\Framework\Http\ServiceContainer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\TerminableInterface;

readonly class AuthServiceMiddleware implements TerminableInterface
{
    public function __construct(
        private FindServiceByTokenAction $findServiceByTokenAction,
        private ServiceContainer $serviceContainer
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

        $service = $this->findServiceByTokenAction->handle($token);

        if (!$service) {
            abort(401);
        }

        $this->serviceContainer->setService($service);

        return $next($request);
    }


    public function terminate(\Symfony\Component\HttpFoundation\Request $request, Response $response)
    {
        $this->serviceContainer->setService(null);
    }
}
