<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use MongoDB\Client;
use MongoDB\Model\DatabaseInfo;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function array_map;
use function getenv;
use function in_array;
use function iterator_to_array;

/** @BeforeMethods({"initDocumentManager", "clearDatabase"}) */
abstract class BaseBench
{
    public const DATABASE_NAME           = 'doctrine_odm_performance';
    private const DEFAULT_MONGODB_SERVER = 'mongodb://localhost:27017';

    /** @var DocumentManager */
    protected static $documentManager;

    protected function getDocumentManager(): DocumentManager
    {
        return self::$documentManager;
    }

    public function initDocumentManager(): void
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../../tests/Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setHydratorDir(__DIR__ . '/../../tests/Hydrators');
        $config->setHydratorNamespace('Hydrators');
        $config->setPersistentCollectionDir(__DIR__ . '/../../tests/PersistentCollections');
        $config->setPersistentCollectionNamespace('PersistentCollections');
        $config->setDefaultDB(self::DATABASE_NAME);
        $config->setMetadataDriverImpl(self::createMetadataDriverImpl());
        $config->setMetadataCache(new ArrayAdapter());

        $client = new Client(
            getenv('DOCTRINE_MONGODB_SERVER') ?: self::DEFAULT_MONGODB_SERVER,
            [],
            ['typeMap' => ['root' => 'array', 'document' => 'array']],
        );

        self::$documentManager = DocumentManager::create($client, $config);
    }

    public function clearDatabase(): void
    {
        // Check if the database exists. Calling listCollections on a non-existing
        // database in a sharded setup will cause an invalid command cursor to be
        // returned
        $client        = self::$documentManager->getClient();
        $databases     = iterator_to_array($client->listDatabases());
        $databaseNames = array_map(static function (DatabaseInfo $database) {
            return $database->getName();
        }, $databases);
        if (! in_array(self::DATABASE_NAME, $databaseNames)) {
            return;
        }

        $collections = $client->getDatabase(self::DATABASE_NAME)->listCollections();

        foreach ($collections as $collection) {
            // See https://jira.mongodb.org/browse/SERVER-16541
            if ($collection->getName() === 'system.indexes') {
                continue;
            }

            $client->getCollection(self::DATABASE_NAME, $collection->getName())->drop();
        }
    }

    protected static function createMetadataDriverImpl(): AttributeDriver
    {
        return AttributeDriver::create(__DIR__ . '/../tests/Documents');
    }
}
