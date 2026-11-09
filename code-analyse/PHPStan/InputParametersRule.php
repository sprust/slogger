<?php

declare(strict_types=1);

namespace CodeAnalyse\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<InClassMethodNode> */
class InputParametersRule implements Rule
{
    public function __construct(private ModuleTypes $types)
    {
    }

    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection()->getName();
        $owner = ModuleTypes::module($class);

        if ($owner === null || !preg_match('/\\\\Domain\\\\(Actions|UseCases)\\\\/', $class) || $node->getMethodReflection()->getName() !== 'handle') {
            return [];
        }
        $errors = [];

        foreach ($node->getMethodReflection()->getVariants() as $variant) {
            foreach ($variant->getParameters() as $parameter) {
                foreach ($this->types->foreignParameters($parameter->getType(), $owner) as $foreign) {
                    $errors[] = RuleErrorBuilder::message("Input \${$parameter->getName()} exposes foreign Parameters $foreign; map local parameters inside Domain.")
                        ->identifier('architecture.foreignParameters')->build();
                }
            }
        }

        return $errors;
    }
}
