<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Tests\Query\Filter\Filter;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use MongoDB\Client;
use MongoDB\Model\DatabaseInfo;
use PHPUnit\Framework\TestCase;

use function array_key_exists;
use function array_map;
use function getenv;
use function in_array;
use function iterator_to_array;
use function preg_match;
use function version_compare;

use const DOCTRINE_MONGODB_DATABASE;
use const DOCTRINE_MONGODB_SERVER;

abstract class BaseTest extends TestCase
{
    protected ?DocumentManager $dm;
    protected UnitOfWork $uow;

    public function setUp(): void
    {
        $this->dm  = $this->createTestDocumentManager();
        $this->uow = $this->dm->getUnitOfWork();
    }

    public function tearDown(): void
    {
        if (! $this->dm) {
            return;
        }

        // Check if the database exists. Calling listCollections on a non-existing
        // database in a sharded setup will cause an invalid command cursor to be
        // returned
        $client        = $this->dm->getClient();
        $databases     = iterator_to_array($client->listDatabases());
        $databaseNames = array_map(static fn (DatabaseInfo $database) => $database->getName(), $databases);
        if (! in_array(DOCTRINE_MONGODB_DATABASE, $databaseNames)) {
            return;
        }

        $collections = $client->selectDatabase(DOCTRINE_MONGODB_DATABASE)->listCollections();

        foreach ($collections as $collection) {
            // See https://jira.mongodb.org/browse/SERVER-16541
            if (preg_match('#^system\.#', $collection->getName())) {
                continue;
            }

            $client->selectCollection(DOCTRINE_MONGODB_DATABASE, $collection->getName())->drop();
        }
    }

    protected function getConfiguration(): Configuration
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../../../../Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setHydratorDir(__DIR__ . '/../../../../Hydrators');
        $config->setHydratorNamespace('Hydrators');
        $config->setPersistentCollectionDir(__DIR__ . '/../../../../PersistentCollections');
        $config->setPersistentCollectionNamespace('PersistentCollections');
        $config->setDefaultDB(DOCTRINE_MONGODB_DATABASE);
        $config->setMetadataDriverImpl($this->createMetadataDriverImpl());

        $config->addFilter('testFilter', Filter::class);
        $config->addFilter('testFilter2', Filter::class);

        return $config;
    }

    /**
     * This method should be dropped, as the checks run here are a subset of what
     * the original assertion checked.
     *
     * @deprecated
     */
    public static function assertArraySubset(array $subset, array $array, bool $checkForObjectIdentity = false, string $message = ''): void
    {
        foreach ($subset as $key => $value) {
            self::assertArrayHasKey($key, $array, $message);

            $check = $checkForObjectIdentity ? 'assertSame' : 'assertEquals';

            self::$check($value, $array[$key], $message);
        }
    }

    protected function createMetadataDriverImpl(): MappingDriver
    {
        return AnnotationDriver::create(__DIR__ . '/../../../../Documents');
    }

    protected function createTestDocumentManager(): DocumentManager
    {
        $config = $this->getConfiguration();
        $client = new Client(getenv('DOCTRINE_MONGODB_SERVER') ?: DOCTRINE_MONGODB_SERVER, [], ['typeMap' => ['root' => 'array', 'document' => 'array']]);

        return DocumentManager::create($client, $config);
    }

    protected function getServerVersion(): string
    {
        $result = $this->dm->getClient()->selectDatabase(DOCTRINE_MONGODB_DATABASE)->command(['buildInfo' => 1])->toArray()[0];

        return $result['version'];
    }

    /** @psalm-param class-string $className */
    protected function skipTestIfNotSharded(string $className): void
    {
        $result = $this->dm->getDocumentDatabase($className)->command(['listCommands' => true])->toArray()[0];

        if (array_key_exists('shardCollection', $result['commands'])) {
            return;
        }

        $this->markTestSkipped('Test skipped because server does not support sharding');
    }

    /** @psalm-param class-string $className */
    protected function skipTestIfSharded(string $className): void
    {
        $result = $this->dm->getDocumentDatabase($className)->command(['listCommands' => true])->toArray()[0];

        if (! array_key_exists('shardCollection', $result['commands'])) {
            return;
        }

        $this->markTestSkipped('Test does not apply on sharded clusters');
    }

    protected function requireVersion(string $installedVersion, string $requiredVersion, ?string $operator, string $message): void
    {
        if (! version_compare($installedVersion, $requiredVersion, $operator)) {
            return;
        }

        $this->markTestSkipped($message);
    }

    protected function skipOnMongoDB42(string $message): void
    {
        $this->requireVersion($this->getServerVersion(), '4.2.0', '>=', $message);
    }

    protected function requireMongoDB42(string $message): void
    {
        $this->requireVersion($this->getServerVersion(), '4.2.0', '<', $message);
    }
}
