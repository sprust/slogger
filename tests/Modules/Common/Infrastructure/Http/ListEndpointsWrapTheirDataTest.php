<?php

namespace Tests\Modules\Common\Infrastructure\Http;

use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * Every endpoint that answers with a list has to answer `{"data": [...]}`.
 *
 * The generated schema says so for all of them, and the panel reads `response.data.data`.
 * A controller returning a plain array of resources sends a bare JSON array instead —
 * Laravel wraps a resource or a resource collection, never an array — and the panel then
 * reads undefined and shows an empty page. That is what the watchers list, its types and
 * its incidents did: no error anywhere, three empty tables and a dropdown with nothing in
 * it.
 *
 * The list endpoints are found by the attribute the schema generator needs on them, so a
 * new one is covered by this the moment it can appear in the schema at all.
 */
class ListEndpointsWrapTheirDataTest extends TestCase
{
    public function testNoListEndpointAnswersWithAPlainArray(): void
    {
        $checked = 0;

        foreach ($this->controllers() as $controller) {
            foreach ($controller->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (!count($method->getAttributes(OaListItemTypeAttribute::class))) {
                    continue;
                }

                ++$checked;

                $type = $method->getReturnType();

                $this->assertInstanceOf(
                    ReflectionNamedType::class,
                    $type,
                    "$controller->name::$method->name() has to declare what it answers with"
                );

                $this->assertNotSame(
                    'array',
                    $type->getName(),
                    "$controller->name::$method->name() answers with a plain array, which "
                    . 'reaches the panel without the `data` the schema promises. Return a '
                    . 'resource collection — Resource::collection(...) — instead.'
                );
            }
        }

        // Otherwise a change to where controllers live would leave this test passing on
        // nothing at all.
        $this->assertGreaterThan(5, $checked, 'No list endpoints were found to check');
    }

    /**
     * @return ReflectionClass<object>[]
     */
    private function controllers(): array
    {
        $finder = new Finder()
            ->files()
            ->name('*Controller.php')
            ->in([
                $this->projectRoot() . '/app/Http/Controllers',
                $this->projectRoot() . '/app/Modules',
            ])
            ->path('Controllers');

        $classes = [];

        foreach ($finder as $file) {
            $class = $this->classOf($file);

            if (class_exists($class)) {
                $classes[] = new ReflectionClass($class);
            }
        }

        return $classes;
    }

    /** Found by walking up rather than counted in dirnames, which a move would break. */
    private function projectRoot(): string
    {
        $directory = __DIR__;

        while (!file_exists("$directory/composer.json")) {
            $parent = dirname($directory);

            if ($parent === $directory) {
                $this->fail('Could not find the project root');
            }

            $directory = $parent;
        }

        return $directory;
    }

    /**
     * @return class-string
     */
    private function classOf(SplFileInfo $file): string
    {
        $relative = str_replace($this->projectRoot() . '/app/', '', $file->getPathname());

        /** @var class-string */
        return 'App\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
    }
}
