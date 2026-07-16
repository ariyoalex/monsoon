<?php

declare(strict_types=1);

define('MONSOON_START', microtime(true));

require_once __DIR__ . '/../vendor/autoload.php';

$config = Monsoon\Kernel\Config::load(__DIR__ . '/..');

define('MONSOON_ENV', $config['APP_ENV'] ?? 'development');

Monsoon\Kernel\ErrorHandler::register();

return $config;
