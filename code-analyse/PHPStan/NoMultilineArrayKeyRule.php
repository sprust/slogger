<?php

declare(strict_types=1);

namespace CodeAnalyse\PHPStan;

use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\ArrayDimFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node>
 */
class NoMultilineArrayKeyRule implements Rule
{
    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $key = match (true) {
            $node instanceof ArrayDimFetch => $node->dim,
            $node instanceof ArrayItem     => $node->key,
            default                        => null,
        };

        if (null === $key || $key->getStartLine() === $key->getEndLine()) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Array key spans several lines; extract it into a variable.')
                ->identifier('ifksco.multilineArrayKey')
                ->line($key->getStartLine())
                ->build(),
        ];
    }
}
