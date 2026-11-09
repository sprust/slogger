<?php

declare(strict_types=1);

namespace App\Services\OpenApiGenerator\Sources\Illuminate;

use Ifksco\OpenApiGenerator\Objects\Json\Other\ServerObject;
use Ifksco\OpenApiGenerator\Sources\Illuminate\BaseRouterParser;

class AdminApiRoutesParser extends BaseRouterParser
{
    public function getName(): string
    {
        return 'admin-api';
    }

    public function getTitle(): string
    {
        return config('app.name') . '-api-scheme';
    }

    public function getVersion(): string
    {
        return '0.1';
    }

    public function getDescription(bool $isPublic): ?string
    {
        return null;
    }

    public function getServers(): array
    {
        return [
            new ServerObject(
                url: config('app.url'),
            ),
        ];
    }

    public function getAdditionalEnums(): array
    {
        return [];
    }

    protected function getPathPrefixes(): array
    {
        return [
            'admin-api/',
        ];
    }
}
