<?php

require_once __DIR__ . '/../../lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';

use Doctrine\Common\ClassLoader,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ODM\MongoDB\Configuration,
    Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver,
    Doctrine\MongoDB\Connection,
    Doctrine\ODM\MongoDB\DocumentManager;

$classLoader = new ClassLoader('Doctrine\Common', __DIR__ . '/../../lib/vendor/doctrine-common/lib');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine\ODM\MongoDB', __DIR__ . '/../../lib');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine\MongoDB', __DIR__ . '/../../lib/vendor/doctrine-mongodb/lib');
$classLoader->register();

$classLoader = new ClassLoader('Symfony', __DIR__ . '/../../lib/vendor');
$classLoader->register();

$classLoader = new ClassLoader('Documents', __DIR__);
$classLoader->register();

$config = new Configuration();

$config->setProxyDir(__DIR__ . '/Proxies');
$config->setProxyNamespace('Proxies');

$config->setHydratorDir(__DIR__ . '/Hydrators');
$config->setHydratorNamespace('Hydrators');

$config->setDefaultDB('doctrine_odm_sandbox');

/*
$config->setLoggerCallable(function(array $log) {
    print_r($log);
});
$config->setMetadataCacheImpl(new ApcCache());
*/

$reader = new AnnotationReader();
$config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/Documents'));

$dm = DocumentManager::create(new Connection(), $config);