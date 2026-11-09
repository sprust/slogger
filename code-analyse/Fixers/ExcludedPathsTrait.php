<?php

declare(strict_types=1);

namespace CodeAnalyse\Fixers;

use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerConfiguration\FixerOptionInterface;
use SplFileInfo;

trait ExcludedPathsTrait
{
    public function supports(SplFileInfo $file): bool
    {
        $path = str_replace('\\', '/', $file->getRealPath() ?: $file->getPathname());

        foreach ($this->configuration['excluded_paths'] as $pattern) {
            if (1 === preg_match($pattern, $path)) {
                return false;
            }
        }

        return true;
    }

    protected function excludedPathsOption(): FixerOptionInterface
    {
        return new FixerOptionBuilder(
            'excluded_paths',
            'Regular expressions; a file whose path matches any of them is left untouched.'
        )
            ->setAllowedTypes(['string[]'])
            ->setDefault([])
            ->getOption();
    }
}
