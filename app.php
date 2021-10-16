<?php

declare(strict_types = 1);

use NassFloPetr\ExchangeRateGrabberManager\Worker;
use NassFloPetr\ExchangeRateGrabberManager\Core\Application;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

\date_default_timezone_set(\getenv('APP_TIMEZONE'));

$application = new Application();

$worker = new Worker($application);

$worker();
