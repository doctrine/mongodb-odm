<?php

declare(strict_types=1);

use Doctrine\Common\Annotations\AnnotationRegistry;

$file = __DIR__ . '/../vendor/autoload.php';

if (! file_exists($file)) {
    throw new RuntimeException('Install dependencies to run test suite.');
}

$loader = require $file;

AnnotationRegistry::registerLoader([$loader, 'loadClass']);
