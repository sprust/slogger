<?php

declare(strict_types=1);

namespace Tests\Services\OpenApi;

use App\Services\OpenApiGenerator\Sources\Illuminate\AdminApiRoutesParser;
use BackedEnum;
use Ifksco\OpenApiGenerator\JsonFileGenerator;
use Ifksco\OpenApiGenerator\Objects\Json\Properties\OaEnumProperty;
use Ifksco\OpenApiGenerator\Objects\Json\Schemes\OaRequestScheme;
use Ifksco\OpenApiGenerator\Sources\Illuminate\Converters\Request\OaRulesParser;
use Ifksco\OpenApiGenerator\Sources\Illuminate\Converters\Responses\OaResponsesParser;
use Ifksco\OpenApiGenerator\Sources\Illuminate\RouterConfigs;
use Ifksco\OpenApiGenerator\Sources\Illuminate\RouterParser;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Services\OpenApi\Fixtures\CreatedController;
use Tests\Services\OpenApi\Fixtures\UnwrappedController;
use Tests\Services\OpenApi\Fixtures\IntegerKind;
use Tests\Services\OpenApi\Fixtures\PublicAdminApiRoutesParser;
use Tests\Services\OpenApi\Fixtures\StringKind;
use Tests\Services\OpenApi\Fixtures\UnionResource;
use Tests\TestCase;

class OpenApiGeneratorTest extends TestCase
{
    /** @return iterable<string, array{class-string<BackedEnum>, string, bool}> */
    public static function enums(): iterable
    {
        yield 'nullable string enum' => [
            StringKind::class,
            'string',
            true,
        ];

        yield 'nullable integer enum' => [
            IntegerKind::class,
            'integer',
            true,
        ];

        yield 'required enum' => [
            StringKind::class,
            'string',
            false,
        ];
    }

    #[DataProvider('enums')]
    public function testEnum(
        string $class,
        string $type,
        bool $nullable
    ): void {
        $property            = $this->app->make(OaEnumProperty::class);
        $property->enumClass = $class;
        $property->enum      = array_column($class::cases(), 'value');
        $property->nullable  = $nullable;
        $schema              = $property->toArray();
        $reference           = [
            '$ref' => '#/components/schemas/' . class_basename($class),
        ];

        self::assertSame(
            $reference,
            $nullable ? $schema['oneOf'][0] : $schema
        );

        if ($nullable) {
            self::assertSame(
                [
                    'type'     => $type,
                    'nullable' => true,
                    'enum'     => [null],
                ],
                $schema['oneOf'][1]
            );
        }
    }

    public function testResourceUnionAndEmptyObject(): void
    {
        $parser = $this->app->make(
            abstract: OaResponsesParser::class,
            parameters: [
                'resourceClass' => UnionResource::class,
            ],
        );
        $schema = json_decode(
            json: json_encode(
                value: $parser->getScheme()->toArray(),
                flags: JSON_THROW_ON_ERROR,
            ),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertCount(
            2,
            $schema['properties']['payload']['oneOf']
        );

        self::assertSame(
            'object',
            $schema['properties']['empty']['type']
        );

        self::assertSame(
            0,
            $schema['properties']['empty']['maxProperties']
        );
    }

    public function testListBodyAndNestedRequiredFields(): void
    {
        $parser = $this->app->make(OaRulesParser::class);
        $schema = $parser->makeSchemeByRules(
            subject: 'batch',
            rules: [
                '*' => [
                    'required',
                    'array',
                ],
                '*.id' => [
                    'required',
                    'integer',
                ],
                '*.label' => [
                    'nullable',
                    'string',
                ],
            ],
            contentType: OaRequestScheme::OA_CONTENT_TYPE_JSON,
        );
        $schema = $schema->toArray();

        self::assertSame(
            'array',
            $schema['type']
        );

        self::assertSame(
            ['id'],
            $schema['items']['required']
        );
    }

    public function testDeclaredStatusAndFreshConfiguration(): void
    {
        $route = new Route(
            methods: 'POST',
            uri: 'fixture',
            action: [
                'uses' => CreatedController::class . '@__invoke',
            ],
        );
        $route->setContainer($this->app);

        $route->middleware('fixture-auth');
        $parser = $this->app->make(
            abstract: RouterParser::class,
            parameters: [
                'route' => $route,
            ],
        );
        $path = $parser->parse();

        self::assertSame(
            [201],
            array_keys($path->responses)
        );

        self::assertFalse(
            RouterConfigs::isSecurityRoute($route)
        );

        config([
            'oa-generator.security_middlewares' => ['fixture-auth'],
        ]);

        self::assertTrue(
            RouterConfigs::isSecurityRoute($route)
        );
    }

    public function testExplicitlyUnwrappedCollection(): void
    {
        $route = new Route(
            methods: 'GET',
            uri: 'unwrapped-fixture',
            action: [
                'uses' => UnwrappedController::class . '@__invoke',
            ],
        );
        $route->setContainer($this->app);
        $parser = $this->app->make(
            abstract: RouterParser::class,
            parameters: [
                'route' => $route,
            ],
        );

        $path     = $parser->parse();
        $response = $path->responses[200]->toArray();

        self::assertSame(
            expected: 'array',
            actual: $response['type']
        );
        self::assertArrayNotHasKey(
            key: 'properties',
            array: $response
        );
    }

    public function testPublicServerVariables(): void
    {
        $generator = $this->app->make(
            abstract: JsonFileGenerator::class,
            parameters: [
                'source' => $this->app->make(PublicAdminApiRoutesParser::class),
            ],
        );
        $disk = Storage::fake('openapi-test');

        $generator->generate(true);
        $generator->saveToDisk(
            filesystem: $disk,
            fileName: 'public',
        );

        $schema = json_decode(
            json: $disk->get('public.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertSame(
            'API server',
            $schema['servers'][0]['description']
        );

        self::assertSame(
            [
                'default'     => 'demo',
                'description' => 'Tenant subdomain',
            ],
            $schema['servers'][0]['variables']['tenant']
        );
    }

    public function testAdminSchemaKeepsEndpointsAndListWrappers(): void
    {
        $source    = $this->app->make(AdminApiRoutesParser::class);
        $generator = $this->app->make(
            abstract: JsonFileGenerator::class,
            parameters: [
                'source' => $source,
            ],
        );
        $disk = Storage::fake('openapi-test');

        $generator->generate(false);
        $generator->saveToDisk(
            filesystem: $disk,
            fileName: 'admin',
        );

        $schema = json_decode(
            json: $disk->get('admin.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertCount(
            78,
            $schema['paths']
        );

        $servicesResponse = $schema['paths']['/admin-api/services']['get']['responses'][200];
        $servicesSchema   = $servicesResponse['content']['application/json']['schema'];

        self::assertSame(
            'array',
            $servicesSchema['properties']['data']['type']
        );

        self::assertArrayNotHasKey(
            'servers',
            $schema
        );

        foreach (array_keys($schema['paths']) as $path) {
            self::assertStringStartsWith(
                '/admin-api/',
                $path
            );
        }
    }
}
