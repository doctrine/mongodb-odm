<?php

declare(strict_types=1);

use Doctrine\Common\Annotations\AnnotationRegistry;

$file = __DIR__ . '/../vendor/autoload.php';

if (! file_exists($file)) {
    throw new RuntimeException('Install dependencies to run test suite.');
}

$loader = require $file;

$loader->add('Doctrine\ODM\MongoDB\Tests', __DIR__ . '/../tests');
$loader->add('Documents', __DIR__);
$loader->add('Stubs', __DIR__);

AnnotationRegistry::registerLoader([$loader, 'loadClass']);
