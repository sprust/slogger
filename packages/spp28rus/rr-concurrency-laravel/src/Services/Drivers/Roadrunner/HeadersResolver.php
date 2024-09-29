<?php

namespace RrConcurrency\Services\Drivers\Roadrunner;

class HeadersResolver
{
    /** @var array<string, mixed|callable> */
    private array $headers = [];

    public function add(string $name, mixed $header): void
    {
        $this->headers[$name] = $header;
    }

    /**
     * @return array<string, mixed|callable>
     */
    public function get(): array
    {
        return $this->headers;
    }
}
