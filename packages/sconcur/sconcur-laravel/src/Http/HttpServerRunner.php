<?php

declare(strict_types=1);

namespace SConcur\Laravel\Http;

use Illuminate\Contracts\Foundation\Application;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use SConcur\Features\HttpServer\HttpServer;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

/**
 * Builds a SConcur HttpServer from config('sconcur.http_server.server') and
 * serves the Laravel HTTP handler in the current process (no JSON config path,
 * no worker script indirection).
 *
 * When launched by the master, $masterPid is the master's pid (injected via the
 * --masterPid argv flag) so the worker self-terminates if the master dies; null
 * for a standalone foreground run.
 */
readonly class HttpServerRunner
{
    public function __construct(
        private ?int $masterPid = null,
    ) {
    }

    public function run(Application $app): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $responseFactory      = new ResponseFactory();

        $psrHttpFactory = new PsrHttpFactory(
            serverRequestFactory: $serverRequestFactory,
            streamFactory: new StreamFactory(),
            uploadedFileFactory: new UploadedFileFactory(),
            responseFactory: $responseFactory,
        );

        $handler = new LaravelHttpHandler(
            app: $app,
            httpFoundationFactory: new HttpFoundationFactory(),
            psrHttpFactory: $psrHttpFactory,
        );

        $this->makeServer($serverRequestFactory, $responseFactory)
            ->serve($handler(...));
    }

    private function makeServer(
        ServerRequestFactory $serverRequestFactory,
        ResponseFactory $responseFactory,
    ): HttpServer {
        // HttpServer::fromArgs() expects a LIST of "--name=value" strings (it skips
        // anything not starting with "--"); bool must be the literal "1"/"0".
        $argv = [];

        foreach ((array) config('sconcur.http_server.server', []) as $key => $value) {
            $argv[] = sprintf(
                '--%s=%s',
                $key,
                is_bool($value) ? ($value ? '1' : '0') : (string) $value,
            );
        }

        if ($this->masterPid !== null) {
            $argv[] = '--masterPid=' . $this->masterPid;
        }

        return HttpServer::fromArgs(
            argv: $argv,
            serverRequestFactory: $serverRequestFactory,
            responseFactory: $responseFactory,
        );
    }
}
