<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionFactory;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionGenerator;

class ConfigurationTest extends BaseTestCase
{
    public function testDefaultPersistentCollectionFactory(): void
    {
        $c       = new Configuration();
        $factory = $c->getPersistentCollectionFactory();
        self::assertInstanceOf(PersistentCollectionFactory::class, $factory);
        self::assertSame($factory, $c->getPersistentCollectionFactory());
    }

    public function testDefaultPersistentCollectionGenerator(): void
    {
        $c = new Configuration();
        $c->setPersistentCollectionDir(__DIR__ . '/../../../../PersistentCollections');
        $c->setPersistentCollectionNamespace('PersistentCollections');
        $generator = $c->getPersistentCollectionGenerator();
        self::assertInstanceOf(PersistentCollectionGenerator::class, $generator);
        self::assertSame($generator, $c->getPersistentCollectionGenerator());
    }

    public function testEnableTransactionalFlush(): void
    {
        $c = new Configuration();

        self::assertFalse($c->isTransactionalFlushEnabled(), 'Transactional flush is disabled by default');

        $c->setUseTransactionalFlush(true);
        self::assertTrue($c->isTransactionalFlushEnabled(), 'Transactional flush is enabled after setTransactionalFlush(true)');

        $c->setUseTransactionalFlush(false);
        self::assertFalse($c->isTransactionalFlushEnabled(), 'Transactional flush is disabled after setTransactionalFlush(false)');
    }
}
