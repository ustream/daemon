<?php
require_once __DIR__ . '/../vendor/autoload.php';

$testLoader = new \Composer\Autoload\ClassLoader();
$testLoader->add('Ustream\DaemonTest', __DIR__);
$testLoader->register(true);