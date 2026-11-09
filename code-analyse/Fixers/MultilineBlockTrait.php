<?php

declare(strict_types=1);

namespace CodeAnalyse\Fixers;

use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\Analyzer\WhitespacesAnalyzer;

use const T_WHITESPACE;

/**
 * Puts the two sides of a bracket pair on lines of their own once anything between them
 * is multiline, and lines its content up one level deeper.
 */
trait MultilineBlockTrait
{
    private function isMultilineBlock(Tokens $tokens, int $openIndex, int $closeIndex): bool
    {
        for ($index = $openIndex + 1; $index < $closeIndex; ++$index) {
            if (str_contains($tokens[$index]->getContent(), "\n")) {
                return true;
            }
        }

        return false;
    }

    private function opensOnItsOwnLine(Tokens $tokens, int $openIndex): bool
    {
        return $tokens[$openIndex + 1]->isWhitespace()
            && str_contains($tokens[$openIndex + 1]->getContent(), "\n");
    }

    /**
     * @param bool $itemsSeparatedByComma whether the block holds a comma separated list, whose items
     *                                    may in turn be spread over lines of their own
     * @param bool $closeOnItsOwnLine     whether the closing token starts a line of its own, which a
     *                                    bracket does and a statement terminator does not
     */
    private function wrapBlock(
        Tokens $tokens,
        int $openIndex,
        int $closeIndex,
        bool $itemsSeparatedByComma,
        bool $closeOnItsOwnLine = true
    ): void {
        $lineEnding    = $this->whitespacesConfig->getLineEnding();
        $indentUnit    = $this->whitespacesConfig->getIndent();
        $indent        = WhitespacesAnalyzer::detectIndent($tokens, $openIndex);
        $contentIndent = $indent . $indentUnit;

        // Only what shares the line with the opening token moves one level deeper, and what is
        // nested in it has to follow; the items below already start where they are going to stay.
        $movesDeeper = !$this->opensOnItsOwnLine($tokens, $openIndex);
        $depth       = 0;

        for ($index = $openIndex + 1; $index < $closeIndex; ++$index) {
            $token = $tokens[$index];

            if ($token->isWhitespace() && str_contains($token->getContent(), "\n")) {
                // Only a line that starts an item of the block lines up with the block itself.
                // Everything else — the rest of an item, whatever is nested in it — carries its
                // own structure, which stays as it is and merely follows the item it belongs to.
                $startsItem = !$itemsSeparatedByComma || $this->startsItem($tokens, $index, $openIndex);

                if (0 === $depth && $startsItem) {
                    $tokens[$index] = new Token(
                        [
                            T_WHITESPACE, Preg::replace(
                                '/\n[ \t]*$/',
                                "\n" . $contentIndent,
                                $token->getContent()
                            ),
                        ]
                    );
                    $movesDeeper = false;
                } elseif ($movesDeeper) {
                    $tokens[$index] = new Token([T_WHITESPACE, str_replace("\n", "\n" . $indentUnit, $token->getContent())]);
                }

                continue;
            }

            $blockType = Tokens::detectBlockType($token);

            if (null !== $blockType) {
                $depth += true === $blockType['isStart'] ? 1 : -1;
            }
        }

        if ($closeOnItsOwnLine) {
            $beforeCloseIndex = $tokens[$closeIndex - 1]->isWhitespace() ? $closeIndex - 1 : $closeIndex;
            $tokens->ensureWhitespaceAtIndex($beforeCloseIndex, 0, $lineEnding . $indent);
        }

        $tokens->ensureWhitespaceAtIndex($openIndex + 1, 0, $lineEnding . $contentIndent);
    }

    private function startsItem(Tokens $tokens, int $whitespaceIndex, int $openIndex): bool
    {
        $previousIndex = $tokens->getPrevMeaningfulToken($whitespaceIndex);

        return $previousIndex === $openIndex || $tokens[$previousIndex]->equals(',');
    }
}
