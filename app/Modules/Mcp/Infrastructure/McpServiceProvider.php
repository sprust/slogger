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
use App\Modules\Mcp\Domain\Actions\Mutations\CountMcpRequestAction;
use App\Modules\Mcp\Domain\Actions\Mutations\CreateMcpAction;
use App\Modules\Mcp\Domain\Actions\Mutations\DeleteMcpAction;
use App\Modules\Mcp\Domain\Actions\Mutations\RegenerateMcpTokenAction;
use App\Modules\Mcp\Domain\Actions\Mutations\UpdateMcpAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpByTokenAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpsAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpSettingsAction;
use App\Modules\Mcp\Domain\Services\McpTracePeriodResolver;
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
use App\Modules\Mcp\Infrastructure\Tools\SearchTraceTreeTool;
use App\Modules\Mcp\Infrastructure\Tools\GetTraceTimeRangeTool;
use App\Modules\Mcp\Infrastructure\Tools\GetTraceDataTool;
use App\Modules\Mcp\Infrastructure\Tools\CompareTraceGroupsTool;
use App\Modules\Mcp\Infrastructure\Tools\SearchTracesTool;
use App\Modules\Mcp\Infrastructure\Tools\GetTraceDataFieldsTool;
use App\Modules\Mcp\Infrastructure\Tools\SearchSloggerLogsTool;
use App\Modules\Mcp\Infrastructure\Tools\AggregateTracesTool;
use App\Modules\Mcp\Infrastructure\Tools\GetTraceFacetsTool;
use App\Modules\Mcp\Infrastructure\Tools\GetTraceTool;
use App\Modules\Mcp\Infrastructure\Tools\GetTraceTreeTool;
use App\Modules\Mcp\Infrastructure\Tools\GetIncidentEventsTool;
use App\Modules\Mcp\Infrastructure\Tools\GetIncidentsTool;
use App\Modules\Mcp\Infrastructure\Tools\GetServicesTool;
use App\Modules\Mcp\Infrastructure\Tools\McpToolFormatter;
use App\Modules\Mcp\Repositories\McpRepository;
use Illuminate\Contracts\Foundation\Application;

class McpServiceProvider extends BaseServiceProvider
{
    /**
     * @var array<class-string<McpToolInterface>>
     */
    private const array TOOLS = [
        GetServicesTool::class,
        GetTraceTimeRangeTool::class,
        GetTraceFacetsTool::class,
        SearchTracesTool::class,
        GetTraceDataFieldsTool::class,
        AggregateTracesTool::class,
        CompareTraceGroupsTool::class,
        GetIncidentsTool::class,
        GetIncidentEventsTool::class,
        GetTraceTool::class,
        GetTraceDataTool::class,
        GetTraceTreeTool::class,
        SearchTraceTreeTool::class,
        SearchSloggerLogsTool::class,
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

        // the period of a trace query may span the whole time traces are kept for
        $this->app->singleton(
            abstract: McpTracePeriodResolver::class,
            concrete: static fn(): McpTracePeriodResolver => new McpTracePeriodResolver(
                maxHours: (int) config('cleaner.lifetime_hours')
            )
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
            CountMcpRequestAction::class,
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
            GetServicesTool::class,
            GetTraceTimeRangeTool::class,
            GetIncidentsTool::class,
            GetIncidentEventsTool::class,
            McpTraceTreeNodeFactory::class,
            FindMcpTraceAction::class,
            FindMcpTraceTreeAction::class,
            FindMcpTraceTreeFilteredAction::class,
            GetTraceTool::class,
            GetTraceDataTool::class,
            GetTraceTreeTool::class,
            SearchTraceTreeTool::class,
        ];
    }
}
