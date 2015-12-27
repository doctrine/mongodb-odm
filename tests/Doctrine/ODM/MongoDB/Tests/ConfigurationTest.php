<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionFactory;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionGenerator;

class ConfigurationTest extends BaseTest
{
    public function testDefaultPersistentCollectionFactory()
    {
        $c = new Configuration();
        $factory = $c->getPersistentCollectionFactory();
        $this->assertInstanceOf(PersistentCollectionFactory::class, $factory);
        $this->assertSame($factory, $c->getPersistentCollectionFactory());
    }

    public function testDefaultPersistentCollectionGenerator()
    {
        $c = new Configuration();
        $c->setPersistentCollectionDir(__DIR__ . '/../../../../PersistentCollections');
        $c->setPersistentCollectionNamespace('PersistentCollections');
        $generator = $c->getPersistentCollectionGenerator();
        $this->assertInstanceOf(PersistentCollectionGenerator::class, $generator);
        $this->assertSame($generator, $c->getPersistentCollectionGenerator());
    }
}
