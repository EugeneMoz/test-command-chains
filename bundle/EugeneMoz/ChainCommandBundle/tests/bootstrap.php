<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Filesystem\Filesystem;

// Ensure the test cache directory exists
$fs = new Filesystem();
$projectRoot = dirname(__DIR__);

// Create var directory and its subdirectories
if (!$fs->exists($projectRoot.'/var')) {
    $fs->mkdir($projectRoot.'/var', 0777);
}

if (!$fs->exists($projectRoot.'/var/cache')) {
    $fs->mkdir($projectRoot.'/var/cache', 0777);
}

if (!$fs->exists($projectRoot.'/var/logs')) {
    $fs->mkdir($projectRoot.'/var/logs', 0777);
}
