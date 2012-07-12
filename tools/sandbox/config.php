<?php

use Doctrine\Common\ClassLoader;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

$file = __DIR__.'/../../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies to run the sandbox.');
}

require_once $file;

AnnotationDriver::registerAnnotationClasses();

$classLoader = new ClassLoader('Documents', __DIR__);
$classLoader->register();

$config = new Configuration();

$config->setProxyDir(__DIR__ . '/Proxies');
$config->setProxyNamespace('Proxies');

$config->setHydratorDir(__DIR__ . '/Hydrators');
$config->setHydratorNamespace('Hydrators');

$config->setDefaultDB('doctrine_odm_sandbox');

// $config->setLoggerCallable(function(array $log) { print_r($log); });
// $config->setMetadataCacheImpl(new Doctrine\Common\Cache\ApcCache());

$config->setMetadataDriverImpl(AnnotationDriver::create(__DIR__ . '/Documents'));

$dm = DocumentManager::create(new Connection(), $config);
