<?php

namespace SLoggerLaravel\Dispatcher\Transporter\Clients;

interface SLoggerTransporterClientInterface
{
    public function dispatch(array $actions): void;
}
