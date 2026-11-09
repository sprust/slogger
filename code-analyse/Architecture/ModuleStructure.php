<?php

declare(strict_types=1);

namespace CodeAnalyse\Architecture;

class ModuleStructure
{
    /** @return list<string> */
    public function errors(string $root): array
    {
        if (!is_dir($root)) {
            return [
                "Modules directory not found: $root",
            ];
        }

        $template = [
            'Domain' => [
                'Actions',
                'Services',
                'Events',
                'Exceptions',
                'UseCases',
            ],
            'Infrastructure' => [
                'Http',
                'Commands',
                'Jobs',
                'Listeners',
                'Services',
                'Tasks',
                'Broadcasting',
                'Protocol',
                'Tools',
                'Prompts',
                'Exceptions',
            ],
            'Repositories' => [
                'Dto',
                'Services',
            ],
            'Transports' => null,
            'Entities'   => null,
            'Parameters' => null,
            'Enums'      => null,
            'Helpers'    => null,
        ];
        $errors = [];

        foreach (glob($root . '/*.php') ?: [] as $file) {
            if (basename($file) !== 'ModulesConfig.php') {
                $errors[] = 'Unexpected file: ' . basename($file);
            }
        }

        foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $module) {
            foreach (glob($module . '/*.php') ?: [] as $file) {
                $errors[] = 'Unexpected file: ' . basename($module) . '/' . basename($file);
            }

            foreach (glob($module . '/Domain/*.php') ?: [] as $file) {
                $errors[] = 'Unexpected file: ' . basename($module) . '/Domain/' . basename($file);
            }

            foreach (glob($module . '/Infrastructure/*.php') ?: [] as $file) {
                if (!str_ends_with($file, 'Provider.php')) {
                    $errors[] = 'Unexpected file: ' . basename($module) . '/Infrastructure/' . basename($file);
                }
            }

            foreach (glob($module . '/*', GLOB_ONLYDIR) ?: [] as $directory) {
                $name = basename($directory);

                if (!array_key_exists($name, $template)) {
                    $errors[] = 'Unexpected directory: ' . basename($module) . '/' . $name;
                    continue;
                }

                if ($template[$name] === null) {
                    continue;
                }

                foreach (glob($directory . '/*', GLOB_ONLYDIR) ?: [] as $child) {
                    if (!in_array(basename($child), $template[$name], true)) {
                        $errors[] = 'Unexpected directory: ' . basename($module) . '/' . $name . '/' . basename($child);
                    }
                }
            }
        }

        return $errors;
    }
}
