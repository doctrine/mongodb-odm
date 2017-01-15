<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

if (!file_exists($file = __DIR__.'/../vendor/autoload.php')) {
    throw new RuntimeException('Install dependencies to run test suite.');
}

$loader = require $file;

$loader->add('Doctrine\ODM\MongoDB\Tests', __DIR__ . '/../tests');
$loader->add('Documents', __DIR__);
$loader->add('Stubs', __DIR__);

AnnotationRegistry::registerLoader([$loader, 'loadClass']);
