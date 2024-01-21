#!/usr/bin/env php
<?php

use RoadRunner\Helpers\ApplicationFactory;
use RoadRunner\Servers\Http\RrHttpServer;
use Spiral\RoadRunner\Worker;

$basePath = __DIR__ . '/../../../../';

require $basePath . 'vendor/autoload.php';

require __DIR__ . '/../fixes/fixes.php';

$app = (new ApplicationFactory($basePath))->createApplication();

(new RrHttpServer($app, Worker::create()))->serve();
