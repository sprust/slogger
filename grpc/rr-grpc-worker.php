#!/usr/bin/env php
<?php

use Laravel\Octane\ApplicationFactory;
use Spiral\RoadRunner\GRPC\Server;
use Spiral\RoadRunner\Worker;

$basePath = __DIR__ . '/../';

require $basePath . 'vendor/autoload.php';

$server = new Server(options: [
    'debug' => false,
]);

$app = (new ApplicationFactory($basePath))->createApplication();

foreach ($app['config']['octane.servers.roadrunner.grpc-services'] as $interface => $serviceClosure) {
    $server->registerService($interface, $app->make($serviceClosure));
}

$server->serve(Worker::create());
