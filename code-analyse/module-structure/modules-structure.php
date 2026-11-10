<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

$root = $argv[1] ?? __DIR__ . '/../../app/Modules';

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
        'Providers',
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

if (!is_dir($root)) {
    $errors[] = "Modules directory not found: $root";
} else {
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
                    $errors[] = 'Unexpected directory: ' . basename($module) . '/' . $name . '/'
                        . basename($child);
                }
            }
        }
    }
}

foreach ($errors as $error) {
    fwrite(STDERR, $error . PHP_EOL);
}

if ($errors === []) {
    echo 'Module structure is valid.' . PHP_EOL;
}

exit($errors === [] ? 0 : 1);
