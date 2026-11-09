<?php

declare(strict_types=1);

namespace CodeAnalyse\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

/**
 * @phpstan-type _Config array{excluded_paths: list<string>}
 *
 * @implements ConfigurableFixerInterface<_Config, _Config>
 *
 * @noinspection PhpInternalEntityUsedInspection
 *               php-cs-fixer marks its tokenizer and base fixer classes as @internal,
 *               yet they are the only API a custom fixer can be built on
 */
class MultilineArrayBracketsFixer extends AbstractFixer implements
    ConfigurableFixerInterface,
    WhitespacesAwareFixerInterface
{
    /** @use ConfigurableFixerTrait<_Config, _Config> */
    use ConfigurableFixerTrait;
    use ExcludedPathsTrait;
    use MultilineBlockTrait;

    public function getName(): string
    {
        return 'Ifksco/multiline_array_brackets';
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'An array holding anything multiline MUST have its brackets on lines of their own.',
            [
                new CodeSample(
                    "<?php\n\$expected = [self::expected(\n    \$purpose,\n    '2025-01-01'\n)];\n",
                ),
            ],
        );
    }

    /**
     * Must run after Ifksco/multiline_method_arguments (31), which is what turns a call
     * inside the array into a multiline one, and before array_indentation (29).
     */
    public function getPriority(): int
    {
        return 30;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(CT::T_ARRAY_BRACKET_OPEN);
    }

    protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([$this->excludedPathsOption()]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        // Going forward wraps an outer array before the ones nested in it, so a nested array
        // is lined up against the line it has just been moved to.
        for ($index = 0; $index < $tokens->count(); ++$index) {
            if (!$tokens[$index]->isGivenKind(CT::T_ARRAY_BRACKET_OPEN)) {
                continue;
            }

            $closeIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_BRACKET, $index);

            if (!$this->isMultilineBlock($tokens, $index, $closeIndex)) {
                continue;
            }

            $this->wrapBlock(
                $tokens,
                $index,
                $closeIndex,
                itemsSeparatedByComma: true
            );
        }
    }
}
