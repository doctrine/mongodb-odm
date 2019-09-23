<?php

namespace Doctrine\ODM\MongoDB\Benchmark;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;

/**
 * @BeforeMethods({"initDocumentManager", "clearDatabase"})
 */
abstract class BaseBench
{
    const DATABASE_NAME = 'doctrine_odm_performance';

    /**
     * @var DocumentManager
     */
    protected static $documentManager;

    /**
     * @return DocumentManager
     */
    protected function getDocumentManager()
    {
        return self::$documentManager;
    }

    public function initDocumentManager()
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
        $config->setMetadataCacheImpl(new ArrayCache());

        $connection = new Connection(
            getenv("DOCTRINE_MONGODB_SERVER") ?: DOCTRINE_MONGODB_SERVER,
            [],
            $config
        );

        self::$documentManager = DocumentManager::create($connection, $config);
    }

    public function clearDatabase()
    {
        // Check if the database exists. Calling listCollections on a non-existing
        // database in a sharded setup will cause an invalid command cursor to be
        // returned
        $databases = self::$documentManager->getConnection()->listDatabases();
        $databaseNames = array_map(function ($database) { return $database['name']; }, $databases['databases']);
        if (! in_array(self::DATABASE_NAME, $databaseNames)) {
            return;
        }

        $collections = self::$documentManager->getConnection()->selectDatabase(self::DATABASE_NAME)->listCollections();

        foreach ($collections as $collection) {
            $collection->drop();
        }
    }

    protected static function createMetadataDriverImpl()
    {
        return AnnotationDriver::create(__DIR__ . '/../tests/Documents');
    }
}
