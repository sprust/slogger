<?php

declare(strict_types=1);

use Deptrac\Deptrac\Contract\Config\Collector\DirectoryConfig;
use Deptrac\Deptrac\Contract\Config\DeptracConfig;
use Deptrac\Deptrac\Contract\Config\Layer;
use Deptrac\Deptrac\Contract\Config\Ruleset;

return static function (DeptracConfig $config): void {
    $dependencies = [
        'Auth'         => ['User'],
        'Cleaner'      => ['Trace', 'Setting'],
        'Logs'         => ['Setting'],
        'Trace'        => ['Service'],
        'Watcher'      => ['Trace', 'Logs', 'Auth'],
        'Notification' => ['Watcher'],
        'Mcp'          => ['Service', 'Watcher', 'Trace', 'Logs', 'Setting'],
    ];

    $patterns = [
        'Domain'         => 'Domain/(?!(Events|Exceptions|UseCases)/)',
        'UseCases'       => 'Domain/UseCases/',
        'Events'         => 'Domain/Events/',
        'Exceptions'     => 'Domain/Exceptions/',
        'Entities'       => 'Entities/',
        'Parameters'     => 'Parameters/',
        'Enums'          => 'Enums/',
        'Helpers'        => 'Helpers/',
        'Repositories'   => 'Repositories/',
        'Transports'     => 'Transports/',
        'Providers'      => 'Infrastructure/[^/]*Provider.php$',
        'Infrastructure' => 'Infrastructure/(?![^/]*Provider.php$)',
    ];

    $modules = [];

    foreach (glob(__DIR__ . '/../app/Modules/*', GLOB_ONLYDIR) ?: [] as $directory) {
        $name = basename($directory);

        foreach ($patterns as $layer => $pattern) {
            $modules[$name][$layer] = Layer::withName("$name.$layer")->collectors(
                DirectoryConfig::create("/app/Modules/$name/$pattern")
            );
        }
    }

    $modelPatterns = [
        'Common'       => 'Abstract[^/]*[.]php$',
        'User'         => 'Users/',
        'Service'      => 'Services/',
        'Setting'      => 'Settings/',
        'Mcp'          => 'Mcps/',
        'Notification' => 'Notifications/',
        'Watcher'      => 'Watchers/',
        'Dashboard'    => 'Traces/TraceMetric[.]php$',
        'Cleaner'      => 'Traces/TraceClearingProcess[.]php$',
        'Trace'        => 'Traces/(?!TraceMetric[.]php$|TraceClearingProcess[.]php$)',
    ];

    foreach ($modelPatterns as $owner => $pattern) {
        if (isset($modules[$owner])) {
            $modules[$owner]['Models'] = Layer::withName("$owner.Models")->collectors(
                DirectoryConfig::create("/app/Models/$pattern")
            );
        }
    }

    $rules = [];

    foreach ($modules as $name => $layers) {
        $common = $name === 'Common' ? [] : ($modules['Common'] ?? []);

        foreach ($layers as $layerName => $layer) {
            $allowed = array_values($layers);
            // Layer constraints are checked separately; these rules express ownership.
            $allowed = [...$allowed, ...array_values($common)];

            foreach ($dependencies[$name] ?? [] as $otherName) {
                $other = $modules[$otherName] ?? [];

                if ($layerName === 'Domain') {
                    foreach (['Domain', 'Entities', 'Parameters', 'Enums', 'Events', 'Exceptions'] as $target) {
                        if (isset($other[$target])) {
                            $allowed[] = $other[$target];
                        }
                    }
                } elseif ($layerName === 'Transports') {
                    foreach (['Entities', 'Parameters', 'Enums'] as $target) {
                        if (isset($other[$target])) {
                            $allowed[] = $other[$target];
                        }
                    }
                } elseif (in_array($layerName, ['UseCases', 'Infrastructure', 'Providers'], true)) {
                    foreach (['Events', 'Exceptions'] as $target) {
                        if (isset($other[$target])) {
                            $allowed[] = $other[$target];
                        }
                    }
                }
            }
            $rules[] = Ruleset::forLayer($layer)->accesses(...$allowed);
        }
    }

    $config->paths('../app/Modules', '../app/Models')
        ->layers(...array_merge(...array_map(array_values(...), array_values($modules))))
        ->rulesets(...$rules);
};
