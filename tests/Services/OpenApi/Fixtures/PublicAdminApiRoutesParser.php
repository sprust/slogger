<?php

declare(strict_types=1);

namespace Tests\Services\OpenApi\Fixtures;

use App\Services\OpenApiGenerator\Sources\Illuminate\AdminApiRoutesParser;
use Ifksco\OpenApiGenerator\Objects\Json\Other\ServerObject;
use Ifksco\OpenApiGenerator\Objects\Json\Other\ServerVariableObject;

class PublicAdminApiRoutesParser extends AdminApiRoutesParser
{
    public function getServers(): array
    {
        return [
            new ServerObject(
                url: 'https://{tenant}.example.test',
                description: 'API server',
                variables: [
                    new ServerVariableObject(
                        name: 'tenant',
                        default: 'demo',
                        description: 'Tenant subdomain',
                    ),
                ],
            ),
        ];
    }
}
