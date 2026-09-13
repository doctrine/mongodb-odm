<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Mapping;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use MongoDB\Client;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function getenv;

use const PHP_VERSION_ID;

/**
 * Builds DocumentManager instances mapped against the isolated benchmark
 * fixtures rather than the test suite's Documents, so that metadata
 * loading benchmarks aren't affected by unrelated changes to test fixtures.
 *
 * Each instance owns its own ClassMetadataFactory, which is what lets
 * these benchmarks measure "cold" metadata loading without artifacts of
 * a previous invocation lingering in the factory's internal cache.
 */
trait MappingBenchTrait
{
    private const DEFAULT_MONGODB_SERVER = 'mongodb://localhost:27017';

    private function createDocumentManager(bool $withMetadataCache = false): DocumentManager
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../../tests/Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setHydratorDir(__DIR__ . '/../../tests/Hydrators');
        $config->setAutoGenerateHydratorClasses(Configuration::AUTOGENERATE_ALWAYS);
        $config->setHydratorNamespace('Hydrators');
        $config->setPersistentCollectionDir(__DIR__ . '/../../tests/PersistentCollections');
        $config->setPersistentCollectionNamespace('PersistentCollections');
        $config->setDefaultDB('doctrine_odm_performance');
        $config->setMetadataDriverImpl(AttributeDriver::create(__DIR__ . '/../Fixtures'));

        if ($withMetadataCache) {
            $config->setMetadataCache(new ArrayAdapter());
        }

        if (PHP_VERSION_ID >= 80400) {
            $config->setUseNativeLazyObject(true);
        } else {
            $config->setUseLazyGhostObject(true);
        }

        $client = new Client(
            getenv('DOCTRINE_MONGODB_SERVER') ?: self::DEFAULT_MONGODB_SERVER,
            [],
            ['typeMap' => ['root' => 'array', 'document' => 'array']],
        );

        return DocumentManager::create($client, $config);
    }
}
