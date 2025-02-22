<?php

use Indigo\Ini\Renderer;
use Supervisor\Configuration\Configuration;
use Supervisor\Configuration\Section\Program;
use Supervisor\Configuration\Section\RpcInterface;
use Supervisor\Configuration\Section\UnixHttpServer;
use Symfony\Component\Process\PhpExecutableFinder;

$renderer         = new Renderer;
$supervisorConfig = new Configuration;

$supervisorConfig->addSection(
    new UnixHttpServer([
        'file' => '/tmp/supervisor.sock',
    ])
);

$supervisorConfig->addSection(
    new RpcInterface(
        name: 'supervisor',
        properties: [
            'supervisor.rpcinterface_factory' => 'supervisor.rpcinterface:make_main_rpcinterface',
            'retries'                         => 3,
        ]
    )
);

$phpFinder = new PhpExecutableFinder();

$phpExec = $phpFinder->find();

if (!$phpExec) {
    throw new \RuntimeException('PHP executable not found');
}

$config = require_once 'config/config.php';

foreach ($config['processes'] as $item) {
    $supervisorConfig->addSection(
        new Program(
            name: 'sl-cron',
            properties: [
                'command'        => 'php /app/artisan cron:start',
                'stdout_logfile' => '/var/log/supervisor/sl-cron-out.log',
                'stderr_logfile' => '/var/log/supervisor/sl-cron-err.log',
                'autostart'      => true,
                'autorestart'    => true,
                'startsecs'      => 0,
            ]
        )
    );
}

echo $renderer->render($supervisorConfig->toArray());
