<?php

namespace SLoggerLaravel\Dispatcher\Transporter;

readonly class TransporterLoader
{
    private string $version;

    public function __construct(private string $path)
    {
        $this->version = '0.0.1';
    }

    public function load(): void
    {
        $url = $this->makeUrl();

        $content = file_get_contents($url);

        file_put_contents($this->path, $content);
    }

    private function makeUrl(): string
    {
        return "https://github.com/sprust/slogger-transporter/releases/download/v$this->version/strans";
    }
}
