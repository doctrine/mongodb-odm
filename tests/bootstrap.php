<?php

$files = array(
    __DIR__.'/../vendor/autoload.php', //submodule autoloading
    __DIR__.'/../../../autoload.php' //composer autoloading
);

foreach ($files as $file){
    if (file_exists($file)) {
        require_once $file;        
        $autoloaded = true;
        break;
    }
}

if(!$autoloaded){    
    throw new RuntimeException('Install dependencies to run test suite.');
}

use Doctrine\Common\ClassLoader;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

$classLoader = new ClassLoader('Doctrine\ODM\MongoDB\Tests', __DIR__ . '/../tests');
$classLoader->register();

$classLoader = new ClassLoader('Documents', __DIR__);
$classLoader->register();

$classLoader = new ClassLoader('Stubs', __DIR__);
$classLoader->register();

AnnotationDriver::registerAnnotationClasses();
