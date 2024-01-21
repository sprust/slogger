#!/usr/bin/env php
<?php

use RoadRunner\Helpers\ApplicationFactory;
use RoadRunner\Servers\Jobs\RrJobsServer;
use Spiral\RoadRunner\Worker;

$basePath = __DIR__ . '/../../../../';

require $basePath . 'vendor/autoload.php';

require __DIR__ . '/../fixes/fixes.php';

$app = (new ApplicationFactory($basePath))->createApplication();

(new RrJobsServer($app, Worker::create()))->serve();
