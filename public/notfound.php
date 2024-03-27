<?php
/**
 * this files handles requests to unknown endpoints
 */

use AmazonMWSSPWrapper\AmazonSP\DebugLogger;

require __DIR__ . '/../vendor/autoload.php';

$logger = new DebugLogger();

echo "Endpoint not implemented: ".$_SERVER['REQUEST_URI'].PHP_EOL;