<?php

require_once 'PHPUnit/Framework.php';
require_once __DIR__ . '/../lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';
require_once __DIR__ . '/Doctrine/ODM/MongoDB/Tests/BaseTest.php';

use Doctrine\Common\ClassLoader;

$classLoader = new ClassLoader('Doctrine\ODM\MongoDB\Tests', __DIR__ . '/../tests');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine\ODM', __DIR__ . '/../lib');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine', __DIR__ . '/../lib/vendor/doctrine-common/lib');
$classLoader->register();

$classLoader = new ClassLoader('Symfony\Components\Yaml', __DIR__ . '/../lib/vendor');
$classLoader->register();

$classLoader = new ClassLoader('Documents', __DIR__);
$classLoader->register();

$classLoader = new ClassLoader('Stubs', __DIR__);
$classLoader->register();