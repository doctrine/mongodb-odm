<?php

if (!file_exists($file = __DIR__.'/../vendor/autoload.php')) {
    throw new RuntimeException('Install dependencies to run test suite.');
}

$loader = require $file;

$loader->add('Doctrine\ODM\MongoDB\Tests', __DIR__ . '/../tests');
$loader->add('Documents', __DIR__);
$loader->add('Stubs', __DIR__);

\Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver::registerAnnotationClasses();
