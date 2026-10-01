<?php

declare(strict_types=1);

namespace App\Services\Clickhouse;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use SConcur\Exceptions\TaskExecutionException;

/**
 * ClickHouse over its HTTP interface.
 *
 * The SQL goes in the body and every value in `param_<name>`, referenced in the SQL as
 * `{name:Type}`: nothing a user typed is ever spliced into a query. Under SConcur the
 * request is made through its non-blocking HTTP client, so a coroutine waiting on
 * ClickHouse gives the process to the others.
 *
 * Each query carries a `query_id` that starts with the scenario it serves
 * (`trace-list-…`), which is how it is found in `system.query_log`.
 */
class ClickhouseClient
{
    private const int READ_CHUNK_BYTES = 65_536;

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ClickhouseConnectionConfig $config,
        private readonly ClickhouseParameterFormatter $parameterFormatter,
    ) {
    }

    /**
     * Rows of the result, each one as a column-keyed array.
     *
     * @param array<string, mixed>      $params   values of `{name:Type}` in the SQL
     * @param array<string, int|string> $settings query-level settings
     *
     * @return list<array<string, mixed>>
     *
     * @throws ClickhouseQueryException
     */
    public function select(string $sql, array $params = [], string $queryIdPrefix = 'query', array $settings = []): array
    {
        $queryId = $this->makeQueryId($queryIdPrefix);

        $response = $this->send(
            sql: $sql,
            params: $params,
            queryId: $queryId,
            settings: [
                'default_format'                         => 'JSONEachRow',
                'output_format_json_quote_64bit_integers' => 0,
                // the client gives up after the timeout, so the server stops the query then too
                'max_execution_time'                      => $this->config->timeoutSeconds,
                ...$settings,
            ]
        );

        return $this->readRows(
            body: $response->getBody(),
            queryId: $queryId
        );
    }

    /**
     * A statement with no result: DDL, `ALTER … DROP PARTITION`.
     *
     * @param array<string, mixed> $params
     *
     * @throws ClickhouseQueryException
     */
    public function command(string $sql, array $params = [], string $queryIdPrefix = 'command'): void
    {
        $response = $this->send(
            sql: $sql,
            params: $params,
            queryId: $this->makeQueryId($queryIdPrefix),
            settings: []
        );

        $response->getBody()->close();
    }

    /**
     * @param array<string, mixed>      $params
     * @param array<string, int|string> $settings
     *
     * @throws ClickhouseQueryException
     */
    private function send(string $sql, array $params, string $queryId, array $settings): ResponseInterface
    {
        $query = [
            'database' => $this->config->database,
            'query_id' => $queryId,
            ...$settings,
        ];

        foreach ($params as $name => $value) {
            $query["param_$name"] = $this->parameterFormatter->format($value);
        }

        $url = sprintf(
            'http://%s:%d/?%s',
            $this->config->host,
            $this->config->port,
            http_build_query($query, encoding_type: PHP_QUERY_RFC3986)
        );

        $request = $this->requestFactory->createRequest('POST', $url)
            ->withHeader('X-ClickHouse-User', $this->config->username)
            ->withHeader('X-ClickHouse-Key', $this->config->password)
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withBody(
                $this->streamFactory->createStream($sql)
            );

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            throw new ClickhouseQueryException(
                message: sprintf('ClickHouse is unreachable: %s', $exception->getMessage()),
                queryId: $queryId,
                previous: $exception
            );
        }

        if ($response->getStatusCode() !== 200) {
            throw new ClickhouseQueryException(
                message: trim($response->getBody()->getContents()),
                code: (int) $response->getHeaderLine('X-ClickHouse-Exception-Code'),
                queryId: $queryId
            );
        }

        return $response;
    }

    /**
     * Reads the body chunk by chunk and decodes it line by line, so the body is never
     * held whole beside the rows made of it.
     *
     * An error that happens after ClickHouse has started answering cannot change the
     * status any more; it arrives as a line of its own in place of a row.
     *
     * @return list<array<string, mixed>>
     *
     * @throws ClickhouseQueryException
     */
    private function readRows(StreamInterface $body, string $queryId): array
    {
        $rows    = [];
        $pending = '';

        while (($chunk = $this->readChunk($body, $queryId)) !== null) {

            if ($chunk === '') {
                continue;
            }

            $pending .= $chunk;

            $lines   = explode("\n", $pending);
            $pending = array_pop($lines);

            foreach ($lines as $line) {
                if ($line !== '') {
                    $rows[] = $this->decodeRow($line, $queryId);
                }
            }
        }

        if ($pending !== '') {
            $rows[] = $this->decodeRow($pending, $queryId);
        }

        return $rows;
    }

    /**
     * The next piece of the body, null once it is over.
     *
     * ClickHouse reports an error it hits mid-answer by cutting the chunked body short,
     * which the transport sees as a broken read.
     *
     * @throws ClickhouseQueryException
     */
    private function readChunk(StreamInterface $body, string $queryId): ?string
    {
        try {
            return $body->eof() ? null : $body->read(self::READ_CHUNK_BYTES);
        } catch (TaskExecutionException $exception) {
            throw new ClickhouseQueryException(
                message: sprintf('ClickHouse broke off the answer: %s', $exception->getMessage()),
                queryId: $queryId,
                previous: $exception
            );
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ClickhouseQueryException
     */
    private function decodeRow(string $line, string $queryId): array
    {
        try {
            $row = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ClickhouseQueryException(
                message: trim($line),
                queryId: $queryId
            );
        }

        if (!is_array($row)) {
            throw new ClickhouseQueryException(
                message: sprintf('Unexpected row: %s', $line),
                queryId: $queryId
            );
        }

        if (array_keys($row) === ['exception']) {
            throw new ClickhouseQueryException(
                message: (string) $row['exception'],
                queryId: $queryId
            );
        }

        return $row;
    }

    private function makeQueryId(string $prefix): string
    {
        return sprintf('%s-%s', $prefix, bin2hex(random_bytes(8)));
    }
}
