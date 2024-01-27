<?php

namespace SLoggerLaravel\Watchers\EntryPoints;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\View\View;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\TraceIdHelper;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class RequestSLoggerWatcher extends AbstractSLoggerWatcher
{
    public function register(): void
    {
        $this->app['events']->listen(RequestHandled::class, [$this, 'handle']);
    }

    public function handle(RequestHandled $event): void
    {
        if (!$this->processor->isActive()) {
            return;
        }

        $requestStartedAt = $this->app[Kernel::class]->requestStartedAt();

        $request  = $event->request;
        $response = $event->response;

        $data = [
            'ip_address'      => $request->ip(),
            'uri'             => str_replace($request->root(), '', $request->fullUrl()) ?: '/',
            'method'          => $request->method(),
            'action'          => optional($request->route())->getActionName(),
            'middlewares'     => array_values(optional($request->route())->gatherMiddleware() ?? []),
            'headers'         => $this->prepareHeaders($request, $request->headers->all()),
            'payload'         => $this->preparePayload($request, $this->getInput($request)),
            'response_status' => $response->getStatusCode(),
            'response'        => $this->prepareResponse($request, $response),
            'duration'        => TraceIdHelper::calcDuration($requestStartedAt),
            'memory'          => round(memory_get_peak_usage(true) / 1024 / 1024, 1),
            ...$this->getAdditionalData(),
        ];

        $this->dispatchTrace(
            type: SLoggerTraceTypeEnum::Request,
            tags: [],
            data: $data,
            loggedAt: $requestStartedAt
        );
    }

    protected function getAdditionalData(): array
    {
        return [];
    }

    protected function prepareHeaders(Request $request, array $headers): array
    {
        return collect($headers)
            ->map(fn($header) => implode(', ', $header))
            ->all();
    }

    protected function preparePayload(Request $request, array $payload): array
    {
        return $payload;
    }

    protected function getInput(Request $request): array
    {
        $files = $request->files->all();

        array_walk_recursive($files, function (&$file) {
            $file = [
                'name' => $file->getClientOriginalName(),
                'size' => $file->isFile() ? ($file->getSize() / 1000) . 'KB' : '0',
            ];
        });

        return array_replace_recursive($request->input(), $files);
    }

    protected function prepareResponse(Request $request, Response $response): array
    {
        if ($response instanceof RedirectResponse) {
            return [
                'redirect' => $response->getTargetUrl(),
            ];
        }

        if ($response instanceof IlluminateResponse && $response->getOriginalContent() instanceof View) {
            return [
                'view' => $response->getOriginalContent()->getPath(),
            ];
        }

        if ($request->acceptsJson()) {
            return json_decode($response->getContent(), true) ?: [];
        }

        return [];
    }
}
