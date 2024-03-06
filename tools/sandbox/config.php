<?php

declare(strict_types=1);

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

$file = __DIR__ . '/../../vendor/autoload.php';

if (! file_exists($file)) {
    throw new RuntimeException('Install dependencies to run this script.');
}

require_once $file;

$config = new Configuration();
$config->setProxyDir(__DIR__ . '/Proxies');
$config->setProxyNamespace('Proxies');
$config->setHydratorDir(__DIR__ . '/Hydrators');
$config->setHydratorNamespace('Hydrators');
$config->setDefaultDB('doctrine_odm_sandbox');
$config->setMetadataCache(new ArrayAdapter());
$config->setMetadataDriverImpl(AttributeDriver::create(__DIR__ . '/Documents'));

$dm = DocumentManager::create(null, $config);
