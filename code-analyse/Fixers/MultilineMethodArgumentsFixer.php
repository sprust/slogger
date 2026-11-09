<?php

declare(strict_types=1);

namespace CodeAnalyse\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\WhitespacesAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;
use function count;
use const T_ATTRIBUTE;
use const T_DOUBLE_ARROW;
use const T_DOUBLE_COLON;
use const T_FUNCTION;
use const T_FOREACH;
use const T_NAMESPACE;
use const T_NEW;
use const T_NS_SEPARATOR;
use const T_NULLSAFE_OBJECT_OPERATOR;
use const T_OBJECT_OPERATOR;
use const T_STRING;
use const T_VARIABLE;

/**
 * @phpstan-type _Config array{
 *     max_length: int,
 *     single_argument: string,
 *     hug_prefix_length: int,
 *     excluded_paths: list<string>
 * }
 *
 * @implements ConfigurableFixerInterface<_Config, _Config>
 *
 * @noinspection PhpInternalEntityUsedInspection
 *               php-cs-fixer marks its tokenizer and base fixer classes as @internal,
 *               yet they are the only API a custom fixer can be built on
 */
class MultilineMethodArgumentsFixer extends AbstractFixer implements
    ConfigurableFixerInterface,
    WhitespacesAwareFixerInterface
{
    /** @use ConfigurableFixerTrait<_Config, _Config> */
    use ConfigurableFixerTrait;
    use ExcludedPathsTrait;
    use MultilineBlockTrait;

    /** A single argument is never split out. */
    public const string SINGLE_ARGUMENT_KEEP = 'keep';

    /** A single argument is split out only when it is named (`name: $value`). */
    public const string SINGLE_ARGUMENT_BREAK_NAMED = 'break_named';

    /** A single argument is split out by length, like any other. */
    public const string SINGLE_ARGUMENT_ALWAYS = 'always';

    public function getName(): string
    {
        return 'Ifksco/multiline_method_arguments';
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            ('Arguments of a single-line method, static or constructor call '
                . 'MUST be split one per line when their total length exceeds the '
                . 'given limit, and the parentheses of any call holding several '
                . 'lines MUST stand on lines of their own.'),
            [
                new CodeSample(
                    "<?php\n\$this->handle(\$booking->id, \$guest->id, true);\n",
                    ['max_length' => 25],
                ),
                new CodeSample(
                    "<?php\n\$this->handle(bookingId: \$existingGuestDocument->booking->id);\n",
                    ['max_length' => 25, 'single_argument' => 'break_named'],
                ),
            ],
        );
    }

    /**
     * Must run before method_argument_space (30) and array_indentation (29),
     * so that they already see the call in its multiline form.
     */
    public function getPriority(): int
    {
        return 31;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound('(');
    }

    protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            new FixerOptionBuilder(
                'max_length',
                'Maximum total length of the arguments kept on a single line, parentheses excluded.'
            )
                ->setAllowedTypes(['int'])
                ->setDefault(25)
                ->getOption(),
            new FixerOptionBuilder(
                'single_argument',
                'How to treat a call that has exactly one argument.'
            )
                ->setAllowedValues([
                    self::SINGLE_ARGUMENT_KEEP,
                    self::SINGLE_ARGUMENT_BREAK_NAMED,
                    self::SINGLE_ARGUMENT_ALWAYS,
                ])
                ->setDefault(self::SINGLE_ARGUMENT_BREAK_NAMED)
                ->getOption(),
            new FixerOptionBuilder(
                'hug_prefix_length',
                ('Up to how many characters may stand before the line break of an '
                    . 'argument list that opens a block and is left as it is.')
            )
                ->setAllowedTypes(['int'])
                ->setDefault(40)
                ->getOption(),
            $this->excludedPathsOption(),
        ]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $this->splitLongArgumentLists($tokens);
        $this->wrapMultilineArgumentLists($tokens);
    }

    /**
     * Going forward splits an outer call before its nested ones, so a nested call
     * is measured and indented against the line it has just been moved to.
     */
    private function splitLongArgumentLists(Tokens $tokens): void
    {
        $argumentsAnalyzer = new ArgumentsAnalyzer();
        $indexBracketDepth = 0;

        for ($index = 0; $index < $tokens->count(); ++$index) {
            $indexBracketDepth = $this->trackIndexBrackets($tokens, $index, $indexBracketDepth);
            $closeIndex        = $this->findArgumentListEnd($tokens, $index);

            // An argument list that already spans several lines is the author's own layout.
            if (
                null === $closeIndex
                || $this->isMultilineBlock($tokens, $index, $closeIndex)
            ) {
                continue;
            }

            // Arguments of a helper or of a built-in function are left on their line.
            if (!$this->isMethodLikeCall($tokens, $index)) {
                continue;
            }

            if (
                0 < $indexBracketDepth
                || $this->isArrayKey($tokens, $closeIndex)
            ) {
                continue;
            }

            $arguments = $argumentsAnalyzer->getArguments($tokens, $index, $closeIndex);

            if (!$this->shouldBeSplit($tokens, $index, $closeIndex, $arguments)) {
                continue;
            }

            $this->split($tokens, $index, $closeIndex, $arguments);
        }
    }

    /**
     * A separate pass, because a call turns multiline only once the calls nested in it have been
     * split, which the first pass gets to after it has already walked past the outer parenthesis.
     */
    private function wrapMultilineArgumentLists(Tokens $tokens): void
    {
        $indexBracketDepth = 0;

        for ($index = 0; $index < $tokens->count(); ++$index) {
            $indexBracketDepth = $this->trackIndexBrackets($tokens, $index, $indexBracketDepth);
            $closeIndex        = $this->findArgumentListEnd($tokens, $index);

            if (
                null === $closeIndex
                || !$this->isMultilineBlock($tokens, $index, $closeIndex)
            ) {
                continue;
            }

            if (
                0 < $indexBracketDepth
                || $this->isArrayKey($tokens, $closeIndex)
            ) {
                continue;
            }

            // A block opened right after the parenthesis is a shape of its own: `foo($id, [`
            // or `foo($id, static function () {` read fine hugging and only cost an
            // indentation level to undo.
            if ($this->hugsBlockAfterShortPrefix($tokens, $index, $closeIndex)) {
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

    /**
     * An array key has nowhere to put the closing parenthesis of a call spread over lines, so a call
     * standing in one is left on its line however long it is. A plain bracket is an index access:
     * an array literal and a destructuring one reach the tokenizer as kinds of their own.
     */
    private function trackIndexBrackets(Tokens $tokens, int $index, int $depth): int
    {
        if ($tokens[$index]->equals('[')) {
            return $depth + 1;
        }

        if ($tokens[$index]->equals(']')) {
            return max(0, $depth - 1);
        }

        return $depth;
    }

    private function isArrayKey(Tokens $tokens, int $closeIndex): bool
    {
        for ($index = $closeIndex + 1; $index < $tokens->count(); ++$index) {
            $token = $tokens[$index];

            if ($token->isGivenKind(T_DOUBLE_ARROW)) {
                return true;
            }

            $blockType = Tokens::detectBlockType($token);

            if (null !== $blockType) {
                if (true !== $blockType['isStart']) {
                    return false;
                }

                $index = $tokens->findBlockEnd($blockType['type'], $index);

                continue;
            }

            if (
                $token->equalsAny([',', ';'])
                || $token->isGivenKind(T_DOUBLE_COLON)
            ) {
                return false;
            }
        }

        return false;
    }

    /**
     * Whether the first line of the argument list is short and ends by opening a block — an array
     * or the body of a closure. A nested call opening there does not count: its own parentheses
     * hold arguments of their own, and those read better with the outer ones spelled out.
     */
    private function hugsBlockAfterShortPrefix(
        Tokens $tokens,
        int $openIndex,
        int $closeIndex
    ): bool {
        for ($index = $openIndex + 1; $index < $closeIndex; ++$index) {
            if (
                !$tokens[$index]->isWhitespace()
                || !str_contains($tokens[$index]->getContent(), "\n")
            ) {
                continue;
            }

            if (
                mb_strlen(
                    $tokens->generatePartialCode($openIndex + 1, $index - 1)
                ) > $this->configuration['hug_prefix_length']
            ) {
                return false;
            }

            $lastIndex = $tokens->getPrevMeaningfulToken($index);

            return $tokens[$lastIndex]->equals('{')
                || $tokens[$lastIndex]->isGivenKind(CT::T_ARRAY_BRACKET_OPEN);
        }

        return false;
    }

    /**
     * Index of the closing parenthesis when the token opens a non-empty argument list of a call.
     */
    private function findArgumentListEnd(Tokens $tokens, int $index): ?int
    {
        if (
            !$tokens[$index]->equals('(')
            || !$this->isCallArgumentList($tokens, $index)
            || $this->isInsideForeachHeader($tokens, $index)
            || $this->isInlineHelper($tokens, $index)
        ) {
            return null;
        }

        $closeIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS, $index);

        return $tokens->getNextMeaningfulToken($index) === $closeIndex ? null : $closeIndex;
    }

    private function isInsideForeachHeader(Tokens $tokens, int $openIndex): bool
    {
        for ($index = $openIndex - 1; $index >= 0; --$index) {
            if ($tokens[$index]->equals(')')) {
                $index = $tokens->findBlockStart(Tokens::BLOCK_TYPE_PARENTHESIS, $index);

                continue;
            }

            if ($tokens[$index]->equalsAny(['{', '}', ';'])) {
                return false;
            }

            if (!$tokens[$index]->equals('(')) {
                continue;
            }

            $previousIndex = $tokens->getPrevMeaningfulToken($index);

            if ($previousIndex !== null && $tokens[$previousIndex]->isGivenKind(T_FOREACH)) {
                return true;
            }
        }

        return false;
    }

    private function isInlineHelper(Tokens $tokens, int $openIndex): bool
    {
        $nameIndex = $tokens->getPrevMeaningfulToken($openIndex);

        if ($nameIndex === null || !$tokens[$nameIndex]->isGivenKind(T_STRING)) {
            return false;
        }

        $qualifierIndex = $this->findQualifier($tokens, $nameIndex);

        if (
            $qualifierIndex !== null && $tokens[$qualifierIndex]->isGivenKind([
                T_OBJECT_OPERATOR,
                T_NULLSAFE_OBJECT_OPERATOR,
                T_DOUBLE_COLON,
                T_NEW,
            ])
        ) {
            return false;
        }

        return in_array(strtolower($tokens[$nameIndex]->getContent()), ['preg_match', 'preg_match_all'], true);
    }

    private function isCallArgumentList(Tokens $tokens, int $openIndex): bool
    {
        $prevIndex = $tokens->getPrevMeaningfulToken($openIndex);

        if (null === $prevIndex) {
            return false;
        }

        $prevToken = $tokens[$prevIndex];

        // $callable(...), $callables[0](...), foo()(...)
        if (
            $prevToken->isGivenKind(T_VARIABLE)
            || $prevToken->equalsAny([']', ')'])
        ) {
            return true;
        }

        if (!$prevToken->isGivenKind(T_STRING)) {
            return false;
        }

        $qualifierIndex = $this->findQualifier($tokens, $prevIndex);

        // What is left after the calls are a declaration and an attribute, neither of which
        // holds arguments; language constructs have token kinds of their own already.
        return null !== $qualifierIndex
            && !$tokens[$qualifierIndex]->isGivenKind([T_FUNCTION, T_ATTRIBUTE]);
    }

    /**
     * A method, static or constructor call, as opposed to a helper or a built-in function whose
     * arguments read better kept on the line they are on.
     */
    private function isMethodLikeCall(Tokens $tokens, int $openIndex): bool
    {
        $prevIndex = $tokens->getPrevMeaningfulToken($openIndex);
        $prevToken = $tokens[$prevIndex];

        if (
            $prevToken->isGivenKind(T_VARIABLE)
            || $prevToken->equalsAny([']', ')'])
        ) {
            return true;
        }

        $qualifierIndex = $this->findQualifier($tokens, $prevIndex);

        return null !== $qualifierIndex
            && $tokens[$qualifierIndex]->isGivenKind([
                T_OBJECT_OPERATOR,
                T_NULLSAFE_OBJECT_OPERATOR,
                T_DOUBLE_COLON,
                T_NEW,
            ]);
    }

    /**
     * The token telling what the name before the parenthesis belongs to. A qualified name reaches
     * the tokenizer as T_STRING (T_NS_SEPARATOR T_STRING)*, so it sits before all of those parts.
     */
    private function findQualifier(Tokens $tokens, int $nameIndex): ?int
    {
        $qualifierIndex = $tokens->getPrevMeaningfulToken($nameIndex);

        while (
            null !== $qualifierIndex
            && $tokens[$qualifierIndex]->isGivenKind([T_NS_SEPARATOR, T_STRING, T_NAMESPACE])
        ) {
            $qualifierIndex = $tokens->getPrevMeaningfulToken($qualifierIndex);
        }

        return $qualifierIndex;
    }

    /**
     * @param array<int, int> $arguments
     */
    private function shouldBeSplit(
        Tokens $tokens,
        int $openIndex,
        int $closeIndex,
        array $arguments
    ): bool {
        if ([] === $arguments) {
            return false;
        }

        if (count($arguments) === 1) {
            $argumentIndex = $tokens->getNextMeaningfulToken($openIndex);

            if (!$tokens[$argumentIndex]->isGivenKind(CT::T_ARRAY_BRACKET_OPEN)) {
                for ($index = $argumentIndex; $index < $closeIndex; ++$index) {
                    if ($tokens[$index]->equals('(') && $this->isCallArgumentList($tokens, $index)) {
                        return true;
                    }
                }
            }
        }

        $length = mb_strlen($tokens->generatePartialCode($openIndex + 1, $closeIndex - 1));

        if ($length <= $this->configuration['max_length']) {
            return false;
        }

        if (count($arguments) > 1) {
            return true;
        }

        return match ($this->configuration['single_argument']) {
            self::SINGLE_ARGUMENT_ALWAYS      => true,
            self::SINGLE_ARGUMENT_BREAK_NAMED => $this->isNamedArgument($tokens, array_key_first($arguments)),
            default                           => false,
        };
    }

    private function isNamedArgument(Tokens $tokens, int $argumentStartIndex): bool
    {
        // ArgumentsAnalyzer keys point right past the separator, which may be whitespace,
        // hence the search for the first meaningful token starts one index earlier.
        $nameIndex = $tokens->getNextMeaningfulToken($argumentStartIndex - 1);

        return null !== $nameIndex && $tokens[$nameIndex]->isGivenKind(CT::T_NAMED_ARGUMENT_NAME);
    }

    /**
     * @param array<int, int> $arguments
     */
    private function split(Tokens $tokens, int $openIndex, int $closeIndex, array $arguments): void
    {
        $lineEnding     = $this->whitespacesConfig->getLineEnding();
        $indent         = WhitespacesAnalyzer::detectIndent($tokens, $openIndex);
        $argumentIndent = $indent . $this->whitespacesConfig->getIndent();

        // Insertions run back to front so that indices ahead of them stay valid.
        $beforeCloseIndex = $tokens[$closeIndex - 1]->isWhitespace()
            ? $closeIndex - 1
            : $closeIndex;
        $tokens->ensureWhitespaceAtIndex($beforeCloseIndex, 0, $lineEnding . $indent);

        foreach (array_reverse(array_keys($arguments)) as $argumentStartIndex) {
            $tokens->ensureWhitespaceAtIndex(
                $argumentStartIndex,
                0,
                $lineEnding . $argumentIndent
            );
        }
    }
}
