<?php

declare(strict_types=1);

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Symfony\Component\Cache\Adapter\ApcuAdapter;

$file = __DIR__ . '/../../vendor/autoload.php';

if (! file_exists($file)) {
    throw new RuntimeException('Install dependencies to run this script.');
}

$loader = require_once $file;
$loader->add('Documents', __DIR__);
AnnotationRegistry::registerLoader([$loader, 'loadClass']);

$config = new Configuration();
$config->setProxyDir(__DIR__ . '/Proxies');
$config->setProxyNamespace('Proxies');
$config->setHydratorDir(__DIR__ . '/Hydrators');
$config->setHydratorNamespace('Hydrators');
$config->setDefaultDB('doctrine_odm_sandbox');
$config->setMetadataCache(new ApcuAdapter());
$config->setMetadataDriverImpl(AnnotationDriver::create(__DIR__ . '/Documents'));

$dm = DocumentManager::create(null, $config);
