<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\ODM\MongoDB\Proxy\InternalProxy;
use Doctrine\ODM\MongoDB\Tests\Query\Filter\Filter;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use MongoDB\Client;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Server;
use MongoDB\Model\DatabaseInfo;
use PHPUnit\Framework\TestCase;
use ProxyManager\Proxy\LazyLoadingInterface;

use function array_key_exists;
use function array_map;
use function count;
use function explode;
use function getenv;
use function implode;
use function in_array;
use function iterator_to_array;
use function parse_url;
use function preg_match;
use function strlen;
use function strpos;
use function substr_replace;
use function version_compare;

use const DOCTRINE_MONGODB_DATABASE;
use const DOCTRINE_MONGODB_SERVER;

abstract class BaseTestCase extends TestCase
{
    protected static ?bool $supportsTransactions;
    protected static bool $allowsTransactions = true;
    protected ?DocumentManager $dm;
    protected UnitOfWork $uow;

    public function setUp(): void
    {
        $this->dm  = static::createTestDocumentManager();
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

    protected static function getConfiguration(): Configuration
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../../../../Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setHydratorDir(__DIR__ . '/../../../../Hydrators');
        $config->setHydratorNamespace('Hydrators');
        $config->setPersistentCollectionDir(__DIR__ . '/../../../../PersistentCollections');
        $config->setPersistentCollectionNamespace('PersistentCollections');
        $config->setDefaultDB(DOCTRINE_MONGODB_DATABASE);
        $config->setMetadataDriverImpl(static::createMetadataDriverImpl());
        $config->setUseLazyGhostObject((bool) $_ENV['USE_LAZY_GHOST_OBJECTS']);

        $config->addFilter('testFilter', Filter::class);
        $config->addFilter('testFilter2', Filter::class);

        // Enable transactions if supported
        $config->setUseTransactionalFlush(static::$allowsTransactions && self::supportsTransactions());

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

    public static function isLazyObject(object $document): bool
    {
        return $document instanceof InternalProxy || $document instanceof LazyLoadingInterface;
    }

    protected static function createMetadataDriverImpl(): MappingDriver
    {
        return AttributeDriver::create(__DIR__ . '/../../../../Documents');
    }

    protected static function createTestDocumentManager(): DocumentManager
    {
        $config = static::getConfiguration();
        $client = new Client(self::getUri());

        return DocumentManager::create($client, $config);
    }

    protected function getServerVersion(): string
    {
        $result = $this->dm->getClient()->selectDatabase(DOCTRINE_MONGODB_DATABASE)->command(['buildInfo' => 1], ['typeMap' => DocumentManager::CLIENT_TYPEMAP])->toArray()[0];

        return $result['version'];
    }

    protected function getPrimaryServer(): Server
    {
        return $this->dm->getClient()->getManager()->selectServer();
    }

    protected function skipTestIfNoTransactionSupport(): void
    {
        if (! self::supportsTransactions()) {
            $this->markTestSkipped('Test requires a topology that supports transactions');
        }
    }

    protected function skipTestIfTransactionalFlushDisabled(): void
    {
        if (! $this->dm?->getConfiguration()->isTransactionalFlushEnabled()) {
            $this->markTestSkipped('Test only applies when transactional flush is enabled');
        }
    }

    protected function skipTestIfTransactionalFlushEnabled(): void
    {
        if ($this->dm?->getConfiguration()->isTransactionalFlushEnabled()) {
            $this->markTestSkipped('Test is not compatible with transactional flush');
        }
    }

    /** @param class-string $className */
    protected function skipTestIfNotSharded(string $className): void
    {
        $result = $this->dm->getDocumentDatabase($className)->command(['listCommands' => true], ['typeMap' => DocumentManager::CLIENT_TYPEMAP])->toArray()[0];

        if (array_key_exists('shardCollection', $result['commands'])) {
            return;
        }

        $this->markTestSkipped('Test skipped because server does not support sharding');
    }

    /** @param class-string $className */
    protected function skipTestIfSharded(string $className): void
    {
        $result = $this->dm->getDocumentDatabase($className)->command(['listCommands' => true], ['typeMap' => DocumentManager::CLIENT_TYPEMAP])->toArray()[0];

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

    protected function requireMongoDB63(string $message): void
    {
        $this->requireVersion($this->getServerVersion(), '6.3.0', '<', $message);
    }

    protected static function getUri(bool $useMultipleMongoses = true): string
    {
        $uri = getenv('DOCTRINE_MONGODB_SERVER') ?: DOCTRINE_MONGODB_SERVER;

        return $useMultipleMongoses ? $uri : self::removeMultipleHosts($uri);
    }

    /**
     * Removes any hosts beyond the first in a URI. This function should only be
     * used with a sharded cluster URI, but that is not enforced.
     */
    protected static function removeMultipleHosts(string $uri): string
    {
        $parts = parse_url($uri);

        self::assertIsArray($parts);

        $hosts = explode(',', $parts['host']);

        // Nothing to do if the URI already has a single mongos host
        if (count($hosts) === 1) {
            return $uri;
        }

        // Re-append port to last host
        if (isset($parts['port'])) {
            $hosts[count($hosts) - 1] .= ':' . $parts['port'];
        }

        $singleHost    = $hosts[0];
        $multipleHosts = implode(',', $hosts);

        $pos = strpos($uri, $multipleHosts);

        self::assertNotFalse($pos);

        return substr_replace($uri, $singleHost, $pos, strlen($multipleHosts));
    }

    protected static function supportsTransactions(): bool
    {
        return self::$supportsTransactions ??= self::detectTransactionSupport();
    }

    private static function detectTransactionSupport(): bool
    {
        $manager = new Manager(self::getUri());

        return $manager->selectServer()->getType() !== Server::TYPE_STANDALONE;
    }
}
