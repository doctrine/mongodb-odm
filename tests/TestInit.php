<?php

require_once 'PHPUnit/Framework.php';
require_once $_SERVER['DOCTRINE2_DIR'] . '/Doctrine/Common/ClassLoader.php';
require_once 'BaseTest.php';

use Doctrine\Common\ClassLoader;

$classLoader = new ClassLoader('Doctrine\ODM', __DIR__ . '/../lib');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine', $_SERVER['DOCTRINE2_DIR']);
$classLoader->register();

$classLoader = new ClassLoader('Symfony', $_SERVER['DOCTRINE2_DIR'] . '/vendor');
$classLoader->register();

$classLoader = new ClassLoader('Documents', __DIR__);
$classLoader->register();