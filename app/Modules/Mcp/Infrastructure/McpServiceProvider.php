<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure;

use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpDataRangeAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpIncidentEventsAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpIncidentsAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpServicesAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceTreeAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceTreeFilteredAction;
use App\Modules\Mcp\Domain\Services\McpTraceTreeNodeFactory;
use App\Modules\Mcp\Domain\Actions\Mutations\CreateMcpAction;
use App\Modules\Mcp\Domain\Actions\Mutations\DeleteMcpAction;
use App\Modules\Mcp\Domain\Actions\Mutations\RegenerateMcpTokenAction;
use App\Modules\Mcp\Domain\Actions\Mutations\TouchMcpAction;
use App\Modules\Mcp\Domain\Actions\Mutations\UpdateMcpAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpByTokenAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpsAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpSettingsAction;
use App\Modules\Mcp\Infrastructure\Prompts\Contracts\McpPromptRegistry;
use App\Modules\Mcp\Infrastructure\Prompts\ExplainIncidentPrompt;
use App\Modules\Mcp\Infrastructure\Prompts\ExplainTracePrompt;
use App\Modules\Mcp\Infrastructure\Prompts\InvestigateErrorsPrompt;
use App\Modules\Mcp\Infrastructure\Prompts\InvestigateLatencyPrompt;
use App\Modules\Mcp\Infrastructure\Protocol\McpHeaderValidator;
use App\Modules\Mcp\Infrastructure\Protocol\McpInstructionsBuilder;
use App\Modules\Mcp\Infrastructure\Protocol\McpJsonEncoder;
use App\Modules\Mcp\Infrastructure\Protocol\McpMessageParser;
use App\Modules\Mcp\Infrastructure\Protocol\McpResultFactory;
use App\Modules\Mcp\Infrastructure\Protocol\McpServer;
use App\Modules\Mcp\Infrastructure\Protocol\McpStringTruncator;
use App\Modules\Mcp\Infrastructure\Protocol\McpVersionValidator;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolRegistry;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchemaCompiler;
use App\Modules\Mcp\Infrastructure\Tools\FindInTraceTreeTool;
use App\Modules\Mcp\Infrastructure\Tools\GetDataRangeTool;
use App\Modules\Mcp\Infrastructure\Tools\GetTraceDataTool;
use App\Modules\Mcp\Infrastructure\Tools\CompareTraceGroupsTool;
use App\Modules\Mcp\Infrastructure\Tools\FindTracesTool;
use App\Modules\Mcp\Infrastructure\Tools\GetIndexStatusTool;
use App\Modules\Mcp\Infrastructure\Tools\ListDynamicIndexesTool;
use App\Modules\Mcp\Infrastructure\Tools\ListTraceDataFieldsTool;
use App\Modules\Mcp\Infrastructure\Tools\SloggerLogsTool;
use App\Modules\Mcp\Infrastructure\Tools\TopTraceGroupsTool;
use App\Modules\Mcp\Infrastructure\Tools\TraceFacetsTool;
use App\Modules\Mcp\Infrastructure\Tools\GetTraceTool;
use App\Modules\Mcp\Infrastructure\Tools\GetTraceTreeTool;
use App\Modules\Mcp\Infrastructure\Tools\GetIncidentEventsTool;
use App\Modules\Mcp\Infrastructure\Tools\ListIncidentsTool;
use App\Modules\Mcp\Infrastructure\Tools\ListServicesTool;
use App\Modules\Mcp\Infrastructure\Tools\McpToolFormatter;
use App\Modules\Mcp\Repositories\McpRepository;
use Illuminate\Contracts\Foundation\Application;

class McpServiceProvider extends BaseServiceProvider
{
    /**
     * @var array<class-string<McpToolInterface>>
     */
    private const array TOOLS = [
        ListServicesTool::class,
        GetDataRangeTool::class,
        TraceFacetsTool::class,
        FindTracesTool::class,
        ListTraceDataFieldsTool::class,
        TopTraceGroupsTool::class,
        CompareTraceGroupsTool::class,
        GetIndexStatusTool::class,
        ListDynamicIndexesTool::class,
        ListIncidentsTool::class,
        GetIncidentEventsTool::class,
        GetTraceTool::class,
        GetTraceDataTool::class,
        GetTraceTreeTool::class,
        FindInTraceTreeTool::class,
        SloggerLogsTool::class,
    ];

    public function boot(): void
    {
        $this->app->singleton(
            McpToolRegistry::class,
            static function (Application $app): McpToolRegistry {
                $tools = [];

                foreach (self::TOOLS as $tool) {
                    $tools[] = $app->make($tool);
                }

                return new McpToolRegistry($tools);
            }
        );

        $this->app->singleton(
            McpPromptRegistry::class,
            static fn(): McpPromptRegistry => new McpPromptRegistry([
                new InvestigateErrorsPrompt(),
                new InvestigateLatencyPrompt(),
                new ExplainIncidentPrompt(),
                new ExplainTracePrompt(),
            ])
        );

        parent::boot();

        $this->app->make(FindMcpSettingsAction::class)->handle();
    }

    protected function getContracts(): array
    {
        return [
            McpRepository::class,
            FindMcpsAction::class,
            FindMcpAction::class,
            FindMcpByTokenAction::class,
            FindMcpSettingsAction::class,
            CreateMcpAction::class,
            UpdateMcpAction::class,
            RegenerateMcpTokenAction::class,
            DeleteMcpAction::class,
            TouchMcpAction::class,
            McpMessageParser::class,
            McpHeaderValidator::class,
            McpVersionValidator::class,
            McpResultFactory::class,
            McpInstructionsBuilder::class,
            McpStringTruncator::class,
            McpJsonEncoder::class,
            McpToolSchemaCompiler::class,
            McpServer::class,
            McpToolFormatter::class,
            FindMcpServicesAction::class,
            FindMcpDataRangeAction::class,
            FindMcpIncidentsAction::class,
            FindMcpIncidentEventsAction::class,
            ListServicesTool::class,
            GetDataRangeTool::class,
            ListIncidentsTool::class,
            GetIncidentEventsTool::class,
            McpTraceTreeNodeFactory::class,
            FindMcpTraceAction::class,
            FindMcpTraceTreeAction::class,
            FindMcpTraceTreeFilteredAction::class,
            GetTraceTool::class,
            GetTraceDataTool::class,
            GetTraceTreeTool::class,
            FindInTraceTreeTool::class,
        ];
    }
}
