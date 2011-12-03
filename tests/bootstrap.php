<?php

require_once __DIR__ . '/../lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';
require_once __DIR__ . '/Doctrine/ODM/MongoDB/Tests/BaseTest.php';

use Doctrine\Common\ClassLoader,
    Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

$classLoader = new ClassLoader('Doctrine\ODM\MongoDB\Tests', __DIR__ . '/../tests');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine\ODM\MongoDB', __DIR__ . '/../lib');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine\MongoDB', __DIR__ . '/../lib/vendor/doctrine-mongodb/lib');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine', __DIR__ . '/../lib/vendor/doctrine-common/lib');
$classLoader->register();

$classLoader = new ClassLoader('Symfony\Component\Yaml', __DIR__ . '/../lib/vendor');
$classLoader->register();

$classLoader = new ClassLoader('Symfony\Component\Console', __DIR__ . '/../lib/vendor');
$classLoader->register();

$classLoader = new ClassLoader('Documents', __DIR__);
$classLoader->register();

$classLoader = new ClassLoader('Stubs', __DIR__);
$classLoader->register();

AnnotationDriver::registerAnnotationClasses();
