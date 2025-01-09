<?php

namespace SLoggerLaravel\Dispatcher\Transporter\Clients;

interface TransporterClientInterface
{
    public function dispatch(array $actions): void;
}
