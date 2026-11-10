<?php

declare(strict_types=1);

use Deptrac\Deptrac\Contract\Config\AnalyserConfig;
use Deptrac\Deptrac\Contract\Config\Collector\DirectoryConfig;
use Deptrac\Deptrac\Contract\Config\DeptracConfig;
use Deptrac\Deptrac\Contract\Config\EmitterType;
use Deptrac\Deptrac\Contract\Config\Layer;
use Deptrac\Deptrac\Contract\Config\Ruleset;

/**
 * Dependencies between modules in one run.
 * Every module gets its own layers, "same module" and "other module" are expressed by explicit layer lists.
 *
 * DirectoryConfig escapes backslashes, so patterns use [A-Za-z0-9_] instead of \w.
 */
return static function (DeptracConfig $config): void {
    $skippingModuleNames = ['Common'];

    $sharedDomainModuleNames = ['Auth'];

    $moduleNames = array_values(
        array_filter(
            array_map(basename(...), glob(__DIR__ . '/../../app/Modules/*', GLOB_ONLYDIR) ?: []),
            static fn(string $moduleName): bool => !in_array($moduleName, $skippingModuleNames, true)
        )
    );

    natcasesort($moduleNames);

    $word = '[A-Za-z0-9_]+';

    /** @var array<string, array{domain: Layer, objects: Layer, repositories: Layer, infrastructure: Layer}> $moduleLayers */
    $moduleLayers = [];

    foreach ($moduleNames as $moduleName) {
        $moduleLayers[$moduleName] = [
            'domain'         => Layer::withName("$moduleName.Domain")->collectors(
                DirectoryConfig::create("/app/Modules/$moduleName/Domain/(?!(Exceptions|Events)/)$word")
            ),
            'objects'        => Layer::withName("$moduleName.Objects")->collectors(
                DirectoryConfig::create("/app/Modules/$moduleName/(Entities|Parameters)/$word")
            ),
            'repositories'   => Layer::withName("$moduleName.Repositories")->collectors(
                DirectoryConfig::create("/app/Modules/$moduleName/Repositories/$word")
            ),
            'infrastructure' => Layer::withName("$moduleName.Infrastructure")->collectors(
                DirectoryConfig::create("/app/Modules/$moduleName/Infrastructure/$word/$word")
            ),
        ];
    }

    $rulesets = [];

    foreach ($moduleLayers as $moduleName => $layers) {
        $otherModuleLayers = array_diff_key($moduleLayers, [$moduleName => true]);

        $otherDomainLayers  = array_column($otherModuleLayers, 'domain');
        $otherObjectsLayers = array_column($otherModuleLayers, 'objects');

        $sharedDomainLayers = array_column(
            array_intersect_key($otherModuleLayers, array_flip($sharedDomainModuleNames)),
            'domain'
        );

        $rulesets[] = Ruleset::forLayer($layers['domain'])->accesses(
            $layers['objects'],
            $layers['repositories'],
            ...$otherDomainLayers,
            ...$otherObjectsLayers,
        );

        $rulesets[] = Ruleset::forLayer($layers['repositories'])->accesses(
            $layers['objects'],
        );

        $rulesets[] = Ruleset::forLayer($layers['infrastructure'])->accesses(
            $layers['domain'],
            $layers['objects'],
            ...$sharedDomainLayers,
            ...$otherObjectsLayers,
        );
    }

    $config
        ->cacheFile(__DIR__ . '/.deptrac-modules.cache')
        ->paths('../../app/Modules')
        ->analyser(AnalyserConfig::create(types: [EmitterType::CLASS_TOKEN], internalTag: 'internal'))
        ->layers(...array_merge(...array_map(array_values(...), array_values($moduleLayers))))
        ->rulesets(...$rulesets);
};
