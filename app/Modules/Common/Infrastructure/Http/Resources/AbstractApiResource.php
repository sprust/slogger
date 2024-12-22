<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use ReflectionProperty;

abstract class AbstractApiResource extends JsonResource
{
    /**
     * @var ReflectionProperty[]|null $reflectionProperties
     */
    protected ?array $reflectionProperties = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray($request = null): array
    {
        $response = [];

        foreach ($this->getReflectionProperties() as $prop) {
            $response[$prop->getName()] = $prop->getValue($this);
        }

        return $response;
    }

    /**
     * @param object[] $list
     *
     * @return static[]
     */
    public static function mapIntoMe(iterable $list): array
    {
        $result = [];

        foreach ($list as $item) {
            $result[] = new static($item);
        }

        return $result;
    }

    public static function makeIfNotNull(mixed $object): ?static
    {
        return $object ? new static($object) : null;
    }

    /**
     * @return ReflectionProperty[]
     */
    protected function getReflectionProperties(): array
    {
        if (!is_null($this->reflectionProperties)) {
            return $this->reflectionProperties;
        }

        $reflectionClass = new \ReflectionClass($this);

        return $this->reflectionProperties = $reflectionClass->getProperties(ReflectionProperty::IS_PRIVATE);
    }
}
