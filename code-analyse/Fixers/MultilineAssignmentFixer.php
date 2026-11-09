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
use const T_COALESCE_EQUAL;
use const T_CONCAT_EQUAL;
use const T_DIV_EQUAL;
use const T_MINUS_EQUAL;
use const T_MUL_EQUAL;
use const T_PLUS_EQUAL;

/**
 * @phpstan-type _Config array{excluded_paths: list<string>}
 *
 * @implements ConfigurableFixerInterface<_Config, _Config>
 *
 * @noinspection PhpInternalEntityUsedInspection
 *               php-cs-fixer marks its tokenizer and base fixer classes as @internal,
 *               yet they are the only API a custom fixer can be built on
 */
class MultilineAssignmentFixer extends AbstractFixer implements
    ConfigurableFixerInterface,
    WhitespacesAwareFixerInterface
{
    /** @use ConfigurableFixerTrait<_Config, _Config> */
    use ConfigurableFixerTrait;
    use ExcludedPathsTrait;
    use MultilineBlockTrait;

    private const array ASSIGNMENT_OPERATORS = [
        T_COALESCE_EQUAL,
        T_CONCAT_EQUAL,
        T_DIV_EQUAL,
        T_MINUS_EQUAL,
        T_MUL_EQUAL,
        T_PLUS_EQUAL,
    ];

    public function getName(): string
    {
        return 'Ifksco/multiline_assignment';
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            ('An assigned expression whose first operand already spans several '
                . 'lines MUST start on a line of its own, with every operand lined '
                . 'up under it.'),
            [
                new CodeSample(
                    "<?php\n\$manualEdit = !\$this->isSame(\n    \$left,\n    \$right\n)\n    || \$other;\n",
                ),
            ],
        );
    }

    /**
     * Must run after binary_operator_spaces (-32), which aligns the assignment operators and
     * would otherwise pull the expression back onto the line of the operator.
     */
    public function getPriority(): int
    {
        return -35;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound('=') || $tokens->isAnyTokenKindsFound(self::ASSIGNMENT_OPERATORS);
    }

    protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([$this->excludedPathsOption()]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = 0; $index < $tokens->count(); ++$index) {
            if (
                !$tokens[$index]->equals('=')
                && !$tokens[$index]->isGivenKind(self::ASSIGNMENT_OPERATORS)
            ) {
                continue;
            }

            $endIndex = $this->findStatementEnd($tokens, $index);

            if (
                null === $endIndex
                || !$this->operandsAreTornApart($tokens, $index, $endIndex)
            ) {
                continue;
            }

            $this->wrapBlock(
                $tokens,
                $index,
                $endIndex,
                itemsSeparatedByComma: false,
                closeOnItsOwnLine: false
            );
        }
    }

    /**
     * Index of the semicolon closing the assignment, or null when the operator belongs to
     * something else than a statement — a default value in a parameter list, a `for` header.
     */
    private function findStatementEnd(Tokens $tokens, int $operatorIndex): ?int
    {
        $depth = 0;

        for ($index = $operatorIndex + 1; $index < $tokens->count(); ++$index) {
            $token = $tokens[$index];

            if ($token->equals(';')) {
                return 0 === $depth ? $index : null;
            }

            if ($token->equals(',') && 0 === $depth) {
                return null;
            }

            $blockType = Tokens::detectBlockType($token);

            if (null === $blockType) {
                continue;
            }

            $depth += true === $blockType['isStart'] ? 1 : -1;

            if ($depth < 0) {
                return null;
            }
        }

        return null;
    }

    /**
     * The layout only hurts when the operands are spread over lines of their own while the first
     * of them keeps hugging the operator — then it alone is indented differently from the rest.
     */
    private function operandsAreTornApart(Tokens $tokens, int $operatorIndex, int $endIndex): bool
    {
        $depth                   = 0;
        $firstOperandIsMultiline = false;

        for ($index = $operatorIndex + 1; $index < $endIndex; ++$index) {
            $token = $tokens[$index];

            if (
                $token->isWhitespace()
                && str_contains($token->getContent(), "\n")
            ) {
                if (0 === $depth) {
                    return $firstOperandIsMultiline;
                }

                $firstOperandIsMultiline = true;

                continue;
            }

            $blockType = Tokens::detectBlockType($token);

            if (null !== $blockType) {
                $depth += true === $blockType['isStart'] ? 1 : -1;
            }
        }

        return false;
    }
}
