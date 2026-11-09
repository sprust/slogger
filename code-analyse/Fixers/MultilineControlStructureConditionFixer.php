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
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;
use const T_ELSEIF;
use const T_FOR;
use const T_IF;
use const T_SWITCH;
use const T_WHILE;

/**
 * @phpstan-type _Config array{excluded_paths: list<string>}
 *
 * @implements ConfigurableFixerInterface<_Config, _Config>
 *
 * @noinspection PhpInternalEntityUsedInspection
 *               php-cs-fixer marks its tokenizer and base fixer classes as @internal,
 *               yet they are the only API a custom fixer can be built on
 */
class MultilineControlStructureConditionFixer extends AbstractFixer implements
    ConfigurableFixerInterface,
    WhitespacesAwareFixerInterface
{
    /** @use ConfigurableFixerTrait<_Config, _Config> */
    use ConfigurableFixerTrait;
    use ExcludedPathsTrait;
    use MultilineBlockTrait;

    private const array CONTROL_STRUCTURES = [T_IF, T_ELSEIF, T_WHILE, T_SWITCH, T_FOR];

    public function getName(): string
    {
        return 'Ifksco/multiline_control_structure_condition';
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            ('A control structure condition spanning several lines MUST start '
                . 'on its own line and have its closing parenthesis on a line of '
                . 'its own.'),
            [
                new CodeSample(
                    ("<?php\nif (!preg_match(\n    '~^/video/~',\n    \$path,\n    "
                        . "\$matches\n)) {\n    return null;\n}\n"),
                ),
            ],
        );
    }

    /**
     * Must run after Ifksco/multiline_method_arguments (31), which is what turns a call
     * inside the condition into a multiline one in the first place.
     */
    public function getPriority(): int
    {
        return 28;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound(self::CONTROL_STRUCTURES);
    }

    protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([$this->excludedPathsOption()]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = 0; $index < $tokens->count(); ++$index) {
            if (!$tokens[$index]->isGivenKind(self::CONTROL_STRUCTURES)) {
                continue;
            }

            $openIndex = $tokens->getNextMeaningfulToken($index);

            if (null === $openIndex || !$tokens[$openIndex]->equals('(')) {
                continue;
            }

            $closeIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS, $openIndex);

            if (!$this->isMultilineBlock($tokens, $openIndex, $closeIndex)) {
                continue;
            }

            $this->wrapBlock(
                $tokens,
                $openIndex,
                $closeIndex,
                itemsSeparatedByComma: false
            );
        }
    }
}
