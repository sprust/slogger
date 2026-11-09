<?php

declare(strict_types=1);

namespace CodeAnalyse\PHPStan;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;

class ModuleTypes
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public static function module(string $class): ?string
    {
        return preg_match('/^App\\\\Modules\\\\([^\\\\]+)\\\\/', $class, $matches) === 1 ? $matches[1] : null;
    }

    /**
     * @param array<string, bool> $visited
     *
     * @return list<string>
     */
    public function foreignParameters(Type $type, string $owner, array $visited = []): array
    {
        $invalid = [];

        foreach ($type->getReferencedClasses() as $class) {
            if (isset($visited[$class]) || !$this->reflectionProvider->hasClass($class)) {
                continue;
            }
            $visited[$class] = true;
            $reflection      = $this->reflectionProvider->getClass($class);

            foreach ([$reflection, ...$reflection->getParents()] as $ancestor) {
                $name   = $ancestor->getName();
                $module = self::module($name);

                if ($module !== null && $module !== $owner && $module !== 'Common' && str_contains($name, '\\Parameters\\')) {
                    $invalid[] = $name;
                }
            }

            if (self::module($class) === null) {
                continue;
            }

            foreach ($reflection->getNativeReflection()->getProperties() as $property) {
                if ($reflection->hasNativeProperty($property->getName())) {
                    $invalid = [
                        ...$invalid, ...$this->foreignParameters(
                            $reflection->getNativeProperty($property->getName())->getReadableType(),
                            $owner,
                            $visited
                        ),
                    ];
                }
            }
        }

        return array_values(array_unique($invalid));
    }
}
