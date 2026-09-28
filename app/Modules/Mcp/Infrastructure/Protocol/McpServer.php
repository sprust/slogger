<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Protocol;

use App\Modules\Mcp\Domain\Actions\Queries\FindMcpSettingsAction;
use App\Modules\Mcp\Entities\McpSettingsObject;
use App\Modules\Mcp\Infrastructure\Prompts\Contracts\McpPromptArgument;
use App\Modules\Mcp\Infrastructure\Prompts\Contracts\McpPromptInterface;
use App\Modules\Mcp\Infrastructure\Prompts\Contracts\McpPromptRegistry;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArgumentsException;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolRegistry;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchemaCompiler;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

readonly class McpServer
{
    public function __construct(
        private FindMcpSettingsAction $findMcpSettingsAction,
        private McpToolRegistry $toolRegistry,
        private McpPromptRegistry $promptRegistry,
        private McpToolSchemaCompiler $schemaCompiler,
        private McpResultFactory $resultFactory,
        private McpInstructionsBuilder $instructionsBuilder,
        private McpStringTruncator $stringTruncator,
        private McpJsonEncoder $jsonEncoder,
        private ValidationFactory $validationFactory
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws McpProtocolException
     */
    public function handle(McpRequestMessage $message): array
    {
        $settings = $this->findMcpSettingsAction->handle();

        return match ($message->method) {
            'server/discover' => $this->discover($settings),
            'tools/list'      => $this->listTools($settings),
            'tools/call'      => $this->callTool($settings, $message),
            'prompts/list'    => $this->listPrompts($settings),
            'prompts/get'     => $this->getPrompt($settings, $message),
            default           => throw new McpProtocolException(
                errorCode: McpErrorCodeEnum::MethodNotFound,
                message: "Method not found: $message->method",
                httpStatus: Response::HTTP_NOT_FOUND,
                requestId: $message->id
            ),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function discover(McpSettingsObject $settings): array
    {
        return $this->resultFactory->cacheable(
            $settings,
            [
                'supportedVersions' => [McpVersionValidator::SUPPORTED_VERSION],
                'capabilities'      => [
                    'tools'   => new stdClass(),
                    'prompts' => new stdClass(),
                ],
                'instructions'      => $this->instructionsBuilder->build($settings),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function listTools(McpSettingsObject $settings): array
    {
        return $this->resultFactory->cacheable(
            $settings,
            [
                'tools' => array_map(
                    fn(McpToolInterface $tool) => [
                        'name'        => $tool->name(),
                        'title'       => $tool->title(),
                        'description' => $tool->description(),
                        'inputSchema' => $this->schemaCompiler->toJsonSchema($tool->schema()),
                        'annotations' => [
                            'readOnlyHint' => true,
                        ],
                    ],
                    $this->toolRegistry->all()
                ),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     *
     * @throws McpProtocolException
     */
    private function callTool(McpSettingsObject $settings, McpRequestMessage $message): array
    {
        $name = $message->params['name'] ?? null;

        if (!is_string($name)) {
            throw $this->invalidParams($message, 'Invalid params: "name" must be a string');
        }

        $tool = $this->toolRegistry->find($name);

        if (is_null($tool)) {
            throw $this->invalidParams($message, "Invalid params: unknown tool [$name]");
        }

        $arguments = $this->validateArguments($message, $tool, $message->params['arguments'] ?? []);

        try {
            $result = $tool->call($arguments);
        } catch (McpToolArgumentsException $exception) {
            throw $this->invalidParams($message, 'Invalid params: ' . $exception->getMessage());
        }

        $data = [
            'installation' => $settings->serverName,
            ...$result->data,
        ];

        if ($result->truncate) {
            $data = $this->stringTruncator->truncate($data, $settings->maxStringLength);
        }

        return $this->resultFactory->complete(
            $settings,
            [
                'content'           => [
                    [
                        'type' => 'text',
                        'text' => $this->jsonEncoder->encode($data),
                    ],
                ],
                'structuredContent' => $data,
                'isError'           => $result->isError,
            ]
        );
    }

    /**
     * @throws McpProtocolException
     */
    private function validateArguments(
        McpRequestMessage $message,
        McpToolInterface $tool,
        mixed $arguments
    ): McpToolArguments {
        if (!is_array($arguments) || (count($arguments) > 0 && array_is_list($arguments))) {
            throw $this->invalidParams($message, 'Invalid params: "arguments" must be an object');
        }

        $known = array_map(
            static fn($property) => $property->name,
            $tool->schema()->properties
        );

        foreach (array_keys($arguments) as $key) {
            if (!in_array($key, $known, true)) {
                throw $this->invalidParams($message, "Invalid params: unknown argument [$key]");
            }
        }

        $validator = $this->validationFactory->make(
            $arguments,
            $this->schemaCompiler->toRules($tool->schema())
        );

        if ($validator->fails()) {
            $field = (string) $validator->errors()->keys()[0];

            throw $this->invalidParams(
                $message,
                sprintf(
                    'Invalid params: argument [%s] is invalid: %s',
                    $field,
                    $validator->errors()->first($field)
                )
            );
        }

        return new McpToolArguments($arguments);
    }

    /**
     * @return array<string, mixed>
     */
    private function listPrompts(McpSettingsObject $settings): array
    {
        return $this->resultFactory->cacheable(
            $settings,
            [
                'prompts' => array_map(
                    static fn(McpPromptInterface $prompt) => [
                        'name'        => $prompt->name(),
                        'title'       => $prompt->title(),
                        'description' => $prompt->description(),
                        'arguments'   => array_map(
                            static fn(McpPromptArgument $argument) => [
                                'name'        => $argument->name,
                                'description' => $argument->description,
                                'required'    => $argument->required,
                            ],
                            $prompt->arguments()
                        ),
                    ],
                    $this->promptRegistry->all()
                ),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     *
     * @throws McpProtocolException
     */
    private function getPrompt(McpSettingsObject $settings, McpRequestMessage $message): array
    {
        $name   = $message->params['name'] ?? null;
        $prompt = is_string($name) ? $this->promptRegistry->find($name) : null;

        if (is_null($prompt)) {
            throw $this->invalidParams($message, sprintf('Invalid params: unknown prompt [%s]', (string) $name));
        }

        $arguments = $message->params['arguments'] ?? [];

        if (!is_array($arguments)) {
            throw $this->invalidParams($message, 'Invalid params: "arguments" must be an object');
        }

        $values = [];

        foreach ($prompt->arguments() as $argument) {
            $value = $arguments[$argument->name] ?? null;

            if (is_null($value) && $argument->required) {
                throw $this->invalidParams($message, "Invalid params: argument [$argument->name] is required");
            }

            if (!is_null($value)) {
                $values[$argument->name] = (string) $value;
            }
        }

        return $this->resultFactory->complete(
            $settings,
            [
                'description' => $prompt->description(),
                'messages'    => [
                    [
                        'role'    => 'user',
                        'content' => [
                            'type' => 'text',
                            'text' => $prompt->text($values),
                        ],
                    ],
                ],
            ]
        );
    }

    private function invalidParams(McpRequestMessage $message, string $text): McpProtocolException
    {
        return new McpProtocolException(
            errorCode: McpErrorCodeEnum::InvalidParams,
            message: $text,
            requestId: $message->id
        );
    }
}
