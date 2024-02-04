<?php

namespace Ifksco\OpenApiGenerator\Router;

use Ifksco\OpenApiGenerator\Attributes\OaAttributeHelper;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Ifksco\OpenApiGenerator\Converters\Request\OaRulesParser;
use Ifksco\OpenApiGenerator\Converters\Responses\OaResponsesParser;
use Ifksco\OpenApiGenerator\Custom\OaBaseCustomResponseInterface;
use Ifksco\OpenApiGenerator\Json\Properties\OaArrayProperty;
use Ifksco\OpenApiGenerator\Json\Properties\OaNumberProperty;
use Ifksco\OpenApiGenerator\Json\Schemes\OaRequestScheme;
use Ifksco\OpenApiGenerator\Json\Schemes\OaScheme;
use Ifksco\OpenApiGenerator\Objects\ParsedRoute;
use Ifksco\OpenApiGenerator\OaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use RuntimeException;

class RouterParser
{
    private ReflectionMethod $reflectionMethod;
    private string $methodView;
    private ?OaListItemTypeAttribute $listItemTypeAttribute;

    public function __construct(private readonly Route $route)
    {
        $controllerReflection = new ReflectionClass($this->route->getController());

        $this->reflectionMethod = $controllerReflection->getMethod(
            Str::parseCallback($this->route->getAction('uses'))[1]
        );

        $this->methodView =
            $this->reflectionMethod->getDeclaringClass()->getName() . '::' . $this->reflectionMethod->getName();

        $this->listItemTypeAttribute = OaAttributeHelper::getListItemTypeAttribute($this->reflectionMethod);
    }

    public function parse(): ParsedRoute
    {
        $parsedRoute = new ParsedRoute();

        $parsedRoute->summary        = OaAttributeHelper::getSummaryAttribute($this->reflectionMethod)?->get();
        $parsedRoute->uri            = str_replace('?}', '}', $this->route->uri());
        $parsedRoute->methods        = $this->route->methods();
        $parsedRoute->middlewares    = array_diff($this->route->middleware(), $this->route->excludedMiddleware());
        $parsedRoute->pathParameters = $this->getPathParameters();
        $parsedRoute->requestScheme  = $this->getRequestScheme();
        $parsedRoute->responses      = $this->getResponsesFromProperties();

        if ($this->listItemTypeAttribute) {
            if (count($parsedRoute->responses) !== 1
                || !array_values($parsedRoute->responses)[0] instanceof OaArrayProperty
            ) {
                throw new RuntimeException("Invalid attribute usage for $this->methodView");
            }
        }

        return $parsedRoute;
    }


    private function getRequestScheme(): ?OaRequestScheme
    {
        $result = null;

        $requestParentClass = OaService::getRequestParentClass();

        foreach ($this->reflectionMethod->getParameters() as $parameter) {
            if (!$parameterType = $parameter->getType()?->getName()) {
                continue;
            }

            if (!class_exists($parameterType)
                || !is_subclass_of($parameterType, $requestParentClass)
            ) {
                continue;
            }

            /**  @var FormRequest $requestInstance */
            $requestInstance = new $parameterType();

            $requestReflection = new ReflectionClass($requestInstance);
            $contentType       = OaAttributeHelper::getRequestAttribute($requestReflection)?->contentType
                ?? OaRequestScheme::OA_CONTENT_TYPE_JSON;

            $result = (new OaRulesParser($requestInstance, $contentType))->getScheme();

            if ($this->listItemTypeAttribute?->isPagination()) {
                $pageProperty           = new OaNumberProperty();
                $pageProperty->required = true;

                $result->properties['page'] = $pageProperty;
            }

            break;
        }

        return $result;
    }

    private function getResponsesFromProperties(): array
    {
        $returnedClassNames = [];

        $rcReturnedType = $this->reflectionMethod->getReturnType();

        if ($rcReturnedType instanceof ReflectionNamedType) {
            $returnedClassNames = [
                $rcReturnedType->getName(),
            ];
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
            if ($returnedClassName === AnonymousResourceCollection::class) {
                if (count($returnedClassNames) !== 1) {
                    throw new RuntimeException(
                        "Method: $this->methodView" . PHP_EOL .
                        'For such method should be one return type: ' . AnonymousResourceCollection::class
                    );
                }

                $returnedClassName = $this->listItemTypeAttribute?->getClassName();

                $returnedClassName = trim($returnedClassName, '\\');

                if (!$returnedClassName || !OaService::isClassResource($returnedClassName)) {
                    throw new RuntimeException(
                        "Method: $this->methodView" . PHP_EOL .
                        "Not found attribute: " . class_basename(OaListItemTypeAttribute::class)
                    );
                }

                $isList       = true;
                $isPagination = $this->listItemTypeAttribute->isPagination();
            } elseif (!OaService::isClassResource($returnedClassName)) {
                continue;
            }

            if (!array_key_exists(200, $schemes)) {
                $schemes[200] = new OaScheme();
            }

            $customProperties = [];

            foreach (config('oa-generator.custom.responses') as $customResponseClass) {
                /** @var OaBaseCustomResponseInterface $customResponseInstance */
                $customResponseInstance = new $customResponseClass();

                if (!$customResponseInstance instanceof OaBaseCustomResponseInterface) {
                    throw new RuntimeException(
                        "Method: $this->methodView" . PHP_EOL .
                        "Custom response should be instance of: " . OaBaseCustomResponseInterface::class
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
                $schemes[200]->oneOf[] = $schema;
            } else {
                $arrayProperty               = new OaArrayProperty();
                $arrayProperty->items        = $schema;
                $arrayProperty->isPagination = $isPagination;

                $schemes[200]->oneOf[] = $arrayProperty;
            }
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

    public function getReflectionMethod(): ReflectionMethod
    {
        return $this->reflectionMethod;
    }

    public function getMethodView(): string
    {
        return $this->methodView;
    }
}
