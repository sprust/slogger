<?php

namespace Tests\Modules\Mcp\Infrastructure\Prompts;

use App\Modules\Mcp\Infrastructure\Prompts\Contracts\McpPromptArgument;
use App\Modules\Mcp\Infrastructure\Prompts\ExplainIncidentPrompt;
use App\Modules\Mcp\Infrastructure\Prompts\ExplainTracePrompt;
use App\Modules\Mcp\Infrastructure\Prompts\InvestigateErrorsPrompt;
use App\Modules\Mcp\Infrastructure\Prompts\InvestigateLatencyPrompt;
use PHPUnit\Framework\TestCase;

class McpPromptsTest extends TestCase
{
    public function testArguments(): void
    {
        $this->assertSame([['service', true], ['period', true]], $this->arguments(new InvestigateErrorsPrompt()));
        $this->assertSame(
            [['service', true], ['period', true], ['type', false]],
            $this->arguments(new InvestigateLatencyPrompt())
        );
        $this->assertSame([['incident_id', true]], $this->arguments(new ExplainIncidentPrompt()));
        $this->assertSame([['trace_id', true]], $this->arguments(new ExplainTracePrompt()));
    }

    public function testErrorsPromptNamesTheTools(): void
    {
        $text = new InvestigateErrorsPrompt()->text(['service' => 'pms', 'period' => 'last 3 hours']);

        $this->assertStringContainsString('"pms"', $text);
        $this->assertStringContainsString('last 3 hours', $text);

        foreach (['list_services', 'get_trace_metrics', 'list_incidents', 'trace_facets', 'find_traces', 'get_trace_tree'] as $tool) {
            $this->assertStringContainsString($tool, $text);
        }
    }

    public function testLatencyPromptWithType(): void
    {
        $text = new InvestigateLatencyPrompt()->text(['service' => 'pms', 'period' => 'today', 'type' => 'request']);

        $this->assertStringContainsString('of type "request"', $text);
        $this->assertStringContainsString('types ["request"]', $text);
        $this->assertStringContainsString('find_in_trace_tree', $text);
    }

    public function testLatencyPromptWithoutTypeDoesNotMentionIt(): void
    {
        $text = new InvestigateLatencyPrompt()->text(['service' => 'pms', 'period' => 'today']);

        $this->assertStringNotContainsString('type', $text);
    }

    public function testIncidentAndTracePrompts(): void
    {
        $this->assertStringContainsString('"inc-1"', new ExplainIncidentPrompt()->text(['incident_id' => 'inc-1']));
        $this->assertStringContainsString('get_incident_events', new ExplainIncidentPrompt()->text(['incident_id' => 'inc-1']));
        $this->assertStringContainsString('"t-1"', new ExplainTracePrompt()->text(['trace_id' => 't-1']));
        $this->assertStringContainsString('get_trace_data', new ExplainTracePrompt()->text(['trace_id' => 't-1']));
    }

    /**
     * @return array<int, array{string, bool}>
     */
    private function arguments(object $prompt): array
    {
        /** @var InvestigateErrorsPrompt $prompt */
        return array_map(
            static fn(McpPromptArgument $argument) => [$argument->name, $argument->required],
            $prompt->arguments()
        );
    }
}
