<?php

namespace Ifksco\OpenApiGenerator\Sources\Illuminate;

use Ifksco\OpenApiGenerator\Attributes\OaAttributeHelper;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Ifksco\OpenApiGenerator\Objects\Json\Properties\OaArrayProperty;
use Ifksco\OpenApiGenerator\Objects\Json\Properties\OaNumberProperty;
use Ifksco\OpenApiGenerator\Objects\Json\Properties\OaObjectProperty;
use Ifksco\OpenApiGenerator\Objects\Json\Schemes\OaRequestScheme;
use Ifksco\OpenApiGenerator\Objects\Json\Schemes\OaScheme;
use Ifksco\OpenApiGenerator\Objects\PathObject;
use Ifksco\OpenApiGenerator\Sources\Illuminate\Converters\Request\OaRulesParser;
use Ifksco\OpenApiGenerator\Sources\Illuminate\Converters\Responses\OaResponsesParser;
use Ifksco\OpenApiGenerator\Sources\Illuminate\Custom\OaBaseCustomResponseInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use RuntimeException;

class RouterParser
{
    private OaRulesParser $rulesParser;

    private ReflectionMethod $reflectionMethod;
    private string $methodView;
    private ?OaListItemTypeAttribute $listItemTypeAttribute;

    /**
     * @throws ReflectionException
     */
    public function __construct(private readonly Route $route)
    {
        $this->rulesParser = new OaRulesParser();

        $controllerReflection = new ReflectionClass(
            $this->route->getController()
        );

        $this->reflectionMethod = $controllerReflection->getMethod(
            Str::parseCallback(
                $this->route->getAction('uses')
            )[1]
        );

        $this->methodView = $this->reflectionMethod->getDeclaringClass()->getName()
            . '::'
            . $this->reflectionMethod->getName();

        $this->listItemTypeAttribute = OaAttributeHelper::getListItemTypeAttribute($this->reflectionMethod);
    }

    /**
     * @throws ReflectionException
     */
    public function parse(): PathObject
    {
        $parsedRoute = new PathObject();

        $parsedRoute->summary        = OaAttributeHelper::getSummaryAttribute($this->reflectionMethod)
            ?->get();
        $parsedRoute->uri            = str_replace('?}', '}', $this->route->uri());
        $parsedRoute->methods        = $this->route->methods();
        $parsedRoute->isSecurity     = RouterConfigs::isSecurityRoute($this->route);
        $parsedRoute->pathParameters = $this->getPathParameters();
        $parsedRoute->requestScheme  = $this->getRequestScheme();
        $parsedRoute->responses      = $this->getResponsesFromProperties();
        $parsedRoute->extensions     = RouterConfigs::getPathExtensions($this->route);

        if ($this->listItemTypeAttribute) {
            if (
                count($parsedRoute->responses) !== 1
                //|| !array_values($parsedRoute->responses)[0] instanceof OaArrayProperty
            ) {
                throw new RuntimeException("Invalid attribute usage for $this->methodView");
            }
        }

        return $parsedRoute;
    }

    private function getRequestScheme(): ?OaRequestScheme
    {
        $result = null;

        foreach ($this->reflectionMethod->getParameters() as $parameter) {
            if (!$parameterType = $parameter->getType()?->getName()) {
                continue;
            }

            if (!RouterConfigs::isClassRequest($parameterType)) {
                continue;
            }

            /** @var FormRequest $requestInstance */
            $requestInstance = new $parameterType();

            $requestReflection = new ReflectionClass($requestInstance);
            $contentType       = OaAttributeHelper::getRequestAttribute($requestReflection)?->contentType
                ?? OaRequestScheme::OA_CONTENT_TYPE_JSON;

            $result = $this->rulesParser->makeSchemeByRequest($requestInstance, $contentType);

            if ($this->listItemTypeAttribute?->isPagination()) {
                $pageProperty           = new OaNumberProperty();
                $pageProperty->required = true;

                $result->properties['page'] = $pageProperty;
            }

            break;
        }

        return $result;
    }

    /**
     * @throws ReflectionException
     */
    private function getResponsesFromProperties(): array
    {
        $returnedClassNames = [];

        $rcReturnedType = $this->reflectionMethod->getReturnType();

        if ($rcReturnedType instanceof ReflectionNamedType) {
            $returnedClassNames = [$rcReturnedType->getName()];
        } elseif ($rcReturnedType instanceof ReflectionUnionType) {
            $returnedClassNames = array_map(
                fn(ReflectionNamedType $type) => $type->getName(),
                $rcReturnedType->getTypes()
            );
        }

        if (!$returnedClassNames) {
            return [];
        }

        $schemes = [];

        $isList       = false;
        $isPagination = false;

        foreach ($returnedClassNames as $returnedClassName) {
            $wrap = null;

            if (is_a($returnedClassName, JsonResource::class, true)) {
                $wrap = $returnedClassName::$wrap;
            }

            $isCollection = $returnedClassName === AnonymousResourceCollection::class;

            if ($isCollection && $this->listItemTypeAttribute === null) {
                throw new RuntimeException("Missing list item attribute for $this->methodView");
            }

            if ($this->listItemTypeAttribute !== null) {
                if (count($returnedClassNames) !== 1) {
                    throw new RuntimeException("List endpoint must have one return type: $this->methodView");
                }

                if (!is_a($returnedClassName, JsonResource::class, true)) {
                    $wrap = 'data';
                }

                $returnedClassName = $this->listItemTypeAttribute?->getClassName();

                $returnedClassName = trim($returnedClassName, '\\');

                if (!$returnedClassName || !RouterConfigs::isClassResource($returnedClassName)) {
                    throw new RuntimeException(
                        "Method: $this->methodView" . PHP_EOL
                            . "Returning class is not resource: $returnedClassName"
                    );
                }

                $isList       = true;
                $isPagination = $this->listItemTypeAttribute->isPagination();
            } elseif (!RouterConfigs::isClassResource($returnedClassName)) {
                continue;
            }

            $statusCodeAttribute = OaAttributeHelper::getResponseStatusCodeAttribute(
                new ReflectionClass($returnedClassName)
            );

            $statusCode = $statusCodeAttribute?->statusCode ?: '200';

            if (!array_key_exists($statusCode, $schemes)) {
                $schemes[$statusCode] = new OaScheme();
            }

            $customProperties = [];

            foreach (config('oa-generator.custom.responses') as $customResponseClass) {
                /** @var OaBaseCustomResponseInterface $customResponseInstance */
                $customResponseInstance = new $customResponseClass();

                if (!$customResponseInstance instanceof OaBaseCustomResponseInterface) {
                    throw new RuntimeException(
                        "Method: $this->methodView" . PHP_EOL
                            . "Custom response should be instance of: "
                            . OaBaseCustomResponseInterface::class
                    );
                }

                if (!$customResponseInstance->is($returnedClassName)) {
                    continue;
                }

                $customProperties = $customResponseInstance->make($this);

                break;
            }

            $schema = (new OaResponsesParser($returnedClassName, $customProperties))->getScheme();

            if (!$isList) {
                $currentScheme = $schema;
            } else {
                $arrayProperty               = new OaArrayProperty();
                $arrayProperty->items        = $schema;
                $arrayProperty->isPagination = $isPagination;

                $currentScheme = $arrayProperty;
            }

            if ($wrap) {
                $wrapProperty = new OaObjectProperty();

                $wrapProperty->properties         = [$wrap => $currentScheme];
                $wrapProperty->requiredProperties = [$wrap];

                $currentScheme = $wrapProperty;
            }

            $schemes[$statusCode]->oneOf[] = $currentScheme;
        }

        foreach ($schemes as $key => $value) {
            if (count($value->oneOf) === 1) {
                $schemes[$key] = $value->oneOf[0];
            }
        }

        return $schemes;
    }

    private function getPathParameters(): array
    {
        preg_match_all('/\{(.*?)\}/', $this->route->uri(), $matches);

        return $matches[1] ?? [];
    }
}
