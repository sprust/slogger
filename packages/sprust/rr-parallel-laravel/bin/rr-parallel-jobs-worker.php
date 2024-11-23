#!/usr/bin/env php
<?php

use Laravel\Octane\ApplicationFactory;
use RrParallel\Services\Drivers\Roadrunner\JobsServer;
use Spiral\RoadRunner\Worker;

$basePath = __DIR__ . '/../../../../';

require $basePath . 'vendor/autoload.php';

$app = (new ApplicationFactory($basePath))->createApplication();

(new JobsServer($app, Worker::create()))->serve();
