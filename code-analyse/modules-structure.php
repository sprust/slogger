<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$errors = (new CodeAnalyse\Architecture\ModuleStructure())->errors($argv[1] ?? __DIR__ . '/../app/Modules');

foreach ($errors as $error) {
    fwrite(STDERR, $error . PHP_EOL);
}

if ($errors === []) {
    echo 'Module structure is valid.' . PHP_EOL;
}
exit($errors === [] ? 0 : 1);
