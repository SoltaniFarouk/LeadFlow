<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Auto-require all connection files in the Connection folder
$connectionPath = __DIR__ . '/../app/Connection/*.php';
foreach (glob($connectionPath) as $connectionFile) {
    require_once $connectionFile;
}