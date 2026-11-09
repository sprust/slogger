<?php

declare(strict_types=1);

namespace CodeAnalyse\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<Node> */
class DomainDependencyRule implements Rule
{
    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $source = $scope->getClassReflection()?->getName();

        if ($source === null || ModuleTypes::module($source) === null) {
            return [];
        }
        $targets = match (true) {
            $node instanceof Name                                            => [$scope->resolveName($node)],
            $node instanceof MethodCall, $node instanceof NullsafeMethodCall => $scope
                ->getType($node->var)
                ->getReferencedClasses(),
            default                                                          => [],
        };
        $errors = [];

        foreach ($targets as $target) {
            if (ModuleTypes::module($target) === null) {
                continue;
            }
            $useCase          = str_contains($source, '\\Domain\\UseCases\\');
            $repository       =
                str_contains($source, '\\Repositories\\')
                && str_ends_with($source, 'Repository');
            $targetRepository =
                str_contains($target, '\\Repositories\\')
                && str_ends_with($target, 'Repository');

            if (
                (
                    $useCase
                    && ($targetRepository
                        || str_contains($target, '\\Domain\\UseCases\\'))
                    && $source !== $target
                )
                || ($repository && $targetRepository && $source !== $target)
            ) {
                $errors[] =
                    RuleErrorBuilder::message(
                        "$source must not depend on $target; extract a shared Action or mapping service."
                    )
                        ->identifier('architecture.domainDependency')
                        ->build();
            }
        }

        return $errors;
    }
}
