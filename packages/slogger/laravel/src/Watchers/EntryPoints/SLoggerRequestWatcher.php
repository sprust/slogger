<?php

namespace SLoggerLaravel\Watchers\EntryPoints;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;
use SLoggerLaravel\Enums\SLoggerTraceStatusEnum;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Events\SLoggerRequestHandling;
use SLoggerLaravel\Helpers\SLoggerArrayHelper;
use SLoggerLaravel\Helpers\SLoggerTraceHelper;
use SLoggerLaravel\Middleware\SLoggerHttpMiddleware;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see SLoggerHttpMiddleware - required for a tracing of requests
 */
class SLoggerRequestWatcher extends AbstractSLoggerWatcher
{
    protected array $requests = [];

    protected array $exceptedPaths = [];
    protected array $maskRequestHeaderFields = [];
    protected array $maskRequestFields = [];

    protected array $pathsWithCleaningOfResponse = [];
    protected array $maskResponseHeaderFields = [];
    protected array $maskResponseFields = [];

    protected function init(): void
    {
        $this->exceptedPaths           = $this->loggerConfig->requestsExceptedPaths();
        $this->maskRequestHeaderFields = $this->loggerConfig->requestsMaskRequestHeaderFields();
        $this->maskRequestFields       = $this->loggerConfig->requestsMaskRequestFields();

        $this->pathsWithCleaningOfResponse = $this->loggerConfig->requestsPathsWithCleaningOfResponse();
        $this->maskResponseHeaderFields    = $this->loggerConfig->requestsMaskResponseHeaderFields();
        $this->maskResponseFields          = $this->loggerConfig->requestsMaskResponseFields();
    }

    public function register(): void
    {
        $this->listenEvent(SLoggerRequestHandling::class, [$this, 'handleRequestHandling']);
        $this->listenEvent(RequestHandled::class, [$this, 'handleRequestHandled']);
    }

    public function handleRequestHandling(SLoggerRequestHandling $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleRequestHandling($event));
    }

    protected function onHandleRequestHandling(SLoggerRequestHandling $event): void
    {
        if ($this->isRequestByPatterns($event->request, $this->exceptedPaths)) {
            return;
        }

        $parentTraceId = $event->parentTraceId;

        $traceId = $this->processor->startAndGetTraceId(
            type: SLoggerTraceTypeEnum::Request->value,
            tags: $this->getTags($event->request),
            data: $this->getCommonRequestData($event->request),
            customParentTraceId: $parentTraceId
        );

        $this->requests[] = [
            'trace_id' => $traceId,
        ];
    }

    public function handleRequestHandled(RequestHandled $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleRequestHandled($event));
    }

    protected function onHandleRequestHandled(RequestHandled $event): void
    {
        if ($this->isRequestByPatterns($event->request, $this->exceptedPaths)) {
            return;
        }

        $requestData = array_pop($this->requests);

        if (!$requestData) {
            return;
        }

        $traceId = $requestData['trace_id'];

        $startedAt = $this->app[Kernel::class]->requestStartedAt();

        $request  = $event->request;
        $response = $event->response;

        $data = [
            ...$this->getCommonRequestData($request),
            'action'      => optional($request->route())->getActionName(),
            'middlewares' => array_values(optional($request->route())->gatherMiddleware() ?? []),
            'request'     => [
                'headers' => $this->prepareHeaders(
                    headers: $request->headers->all(),
                    maskHeaderFields: $this->maskRequestHeaderFields
                ),
                'payload' => $this->prepareRequestPayload(
                    request: $request,
                    payload: $this->getInput($request)
                ),
            ],
            'response'    => [
                'status'  => $response->getStatusCode(),
                'headers' => $this->prepareHeaders(
                    headers: $response->headers->all(),
                    maskHeaderFields: $this->maskResponseHeaderFields
                ),
                'data'    => $this->prepareResponseData(
                    request: $request,
                    response: $response
                ),
            ],
            ...$this->getAdditionalData(),
        ];

        $this->processor->stop(
            traceId: $traceId,
            status: $response->isSuccessful()
                ? SLoggerTraceStatusEnum::Success->value
                : SLoggerTraceStatusEnum::Failed->value,
            data: $data,
            duration: SLoggerTraceHelper::calcDuration($startedAt)
        );
    }

    protected function getCommonRequestData(Request $request): array
    {
        return [
            'ip_address' => $request->ip(),
            'uri'        => str_replace($request->root(), '', $request->fullUrl()) ?: '/',
            'method'     => $request->method(),
        ];
    }

    protected function getTags(Request $request): array
    {
        return [
            $request->getPathInfo(),
        ];
    }

    protected function getAdditionalData(): array
    {
        return [];
    }

    protected function prepareHeaders(array $headers, array $maskHeaderFields): array
    {
        return collect($this->clearHeaders($headers, $maskHeaderFields))
            ->map(fn($header) => implode(', ', $header))
            ->all();
    }

    protected function prepareRequestPayload(Request $request, array $payload): array
    {
        return $this->maskArrayByPatterns(
            data: $payload,
            patterns: $this->maskRequestFields
        );
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

    protected function prepareResponseData(Request $request, Response $response): array
    {
        if ($this->isRequestByPatterns($request, $this->pathsWithCleaningOfResponse)) {
            return [
                'data' => "<cleaned>",
            ];
        }

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
            return $this->maskArrayByPatterns(
                data: json_decode($response->getContent(), true) ?: [],
                patterns: $this->maskResponseFields
            );
        }

        return [];
    }

    protected function isRequestByPatterns(Request $request, array $patterns): bool
    {
        $path = trim($request->getPathInfo(), '/');

        foreach ($patterns as $pattern) {
            $pattern = trim($pattern, '/');

            if (Str::is($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    protected function clearHeaders(array $headers, array $maskFieldNames): array
    {
        foreach ($maskFieldNames as $fieldName) {
            $fieldName = SLoggerArrayHelper::findKeyInsensitive($headers, $fieldName);

            if (!$fieldName) {
                continue;
            }

            $value = $headers[$fieldName];

            if (!is_array($value)) {
                $len = Str::length($value);

                Arr::set($headers, $fieldName, "<cleaned:len-$len>");
            } else {
                foreach ($value as $key => $itemValue) {
                    $len = Str::length($itemValue);

                    Arr::set($headers, "$fieldName.$key", "<cleaned:len-$len>");
                }
            }
        }

        return $headers;
    }

    protected function maskArrayByPatterns(array $data, array $patterns): array
    {
        $result = [];

        $data = Arr::dot($data);

        foreach ($data as $key => $value) {
            if (Str::is($patterns, $key)) {
                if (!is_string($value) && !is_numeric($value)) {
                    $value = '********';
                } else {
                    $value = Str::mask(
                        string: (string) $value,
                        character: '*',
                        index: ceil(Str::length($value) / 4)
                    );
                }
            }

            Arr::set($result, $key, $value);
        }

        return $result;
    }
}
