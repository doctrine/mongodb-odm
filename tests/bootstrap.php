<?php

$file = __DIR__.'/../vendor/.composer/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies to run test suite.');
}

require_once $file;

use Doctrine\Common\ClassLoader;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

$classLoader = new ClassLoader('Doctrine\ODM\MongoDB\Tests', __DIR__ . '/../tests');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine\ODM\MongoDB', __DIR__ . '/../lib');
$classLoader->register();

$classLoader = new ClassLoader('Documents', __DIR__);
$classLoader->register();

$classLoader = new ClassLoader('Stubs', __DIR__);
$classLoader->register();

AnnotationDriver::registerAnnotationClasses();
