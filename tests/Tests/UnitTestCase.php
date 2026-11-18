<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\ODM\MongoDB\Proxy\Factory\NativeLazyObjectFactory;
use Doctrine\ODM\MongoDB\Proxy\InternalProxy;
use Doctrine\ODM\MongoDB\Tests\Query\Filter\Filter;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\Persistence\Mapping\Driver\FileClassLocator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use MongoDB\Client;
use PHPUnit\Framework\TestCase;
use ProxyManager\Proxy\LazyLoadingInterface;

use function assert;
use function class_exists;
use function count;
use function explode;
use function getenv;
use function implode;
use function parse_url;
use function strlen;
use function strpos;
use function substr_replace;

use const DOCTRINE_MONGODB_DATABASE;
use const DOCTRINE_MONGODB_SERVER;

/**
 * Base class for tests that need a document manager but never contact the server.
 *
 * The MongoDB driver connects lazily, so building a Client is free. Avoid adding
 * anything here that issues a command; BaseTestCase extends this class with the
 * helpers that do.
 */
abstract class UnitTestCase extends TestCase
{
    protected Configuration $config;
    protected TypeRegistry $typeRegistry;
    protected ?DocumentManager $dm;
    protected UnitOfWork $uow;

    protected function setUp(): void
    {
        $this->dm     = static::createTestDocumentManager();
        $this->config = $this->dm->getConfiguration();
        $this->uow    = $this->dm->getUnitOfWork();

        $typeRegistry = $this->config->getTypeProvider();
        assert($typeRegistry instanceof TypeRegistry);
        $this->typeRegistry = $typeRegistry;
    }

    protected static function getConfiguration(): Configuration
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setHydratorDir(__DIR__ . '/../Hydrators');
        $config->setHydratorNamespace('Hydrators');
        $config->setPersistentCollectionDir(__DIR__ . '/../PersistentCollections');
        $config->setPersistentCollectionNamespace('PersistentCollections');
        $config->setDefaultDB(DOCTRINE_MONGODB_DATABASE);
        $config->setMetadataDriverImpl(static::createMetadataDriverImpl());
        $config->setTypeProvider(new TypeRegistry());

        if ($_ENV['USE_NATIVE_LAZY_OBJECT']) {
            $config->setUseNativeLazyObject(true);
        } elseif ($_ENV['USE_LAZY_GHOST_OBJECT']) {
            $config->setUseLazyGhostObject(true);
        }

        if ($config->isNativeLazyObjectEnabled()) {
            NativeLazyObjectFactory::enableTracking();
        }

        $config->addFilter('testFilter', Filter::class);
        $config->addFilter('testFilter2', Filter::class);

        $config->setUseTransactionalFlush(static::shouldUseTransactionalFlush());

        return $config;
    }

    protected static function shouldUseTransactionalFlush(): bool
    {
        return false;
    }

    /**
     * This method should be dropped, as the checks run here are a subset of what
     * the original assertion checked.
     *
     * @deprecated
     *
     * @param array<array-key, mixed> $subset
     * @param array<array-key, mixed> $array
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
        if ($_ENV['USE_NATIVE_LAZY_OBJECT']) {
            return NativeLazyObjectFactory::isLazyObject($document);
        }

        return $document instanceof InternalProxy || $document instanceof LazyLoadingInterface;
    }

    protected static function createMetadataDriverImpl(): MappingDriver
    {
        $paths = [__DIR__ . '/../Documents'];

        // Available in Doctrine Persistence 4.1+
        if (class_exists(FileClassLocator::class)) {
            $paths = FileClassLocator::createFromDirectories($paths);
        }

        return AttributeDriver::create($paths);
    }

    protected static function createTestDocumentManager(): DocumentManager
    {
        $config = static::getConfiguration();
        $client = new Client(self::getUri());

        return DocumentManager::create($client, $config);
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
}
