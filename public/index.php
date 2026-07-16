<?php
declare(strict_types=1);

/**
 * Monsoon CMS — Front Controller
 * All requests route through this file.
 */

$config = require_once __DIR__ . '/../kernel/bootstrap.php';

$kernel = new \Monsoon\Kernel\Kernel($config);
$kernel->handle();
