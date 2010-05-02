<?php

require_once 'PHPUnit/Framework.php';
require_once '/Users/jwage/Sites/doctrine2git/lib/Doctrine/Common/ClassLoader.php';
require_once 'BaseTest.php';

use Doctrine\Common\ClassLoader;

$classLoader = new ClassLoader('Doctrine\ODM', __DIR__ . '/../lib');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine', '/Users/jwage/Sites/doctrine2git/lib');
$classLoader->register();

$classLoader = new ClassLoader('Documents', __DIR__);
$classLoader->register();