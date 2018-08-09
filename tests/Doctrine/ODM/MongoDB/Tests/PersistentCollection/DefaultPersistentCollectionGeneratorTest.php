<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\PersistentCollection;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\PersistentCollection\DefaultPersistentCollectionGenerator;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

/**
 * Tests aims to check if classes generated for various PHP versions are correct (i.e. parses).
 */
class DefaultPersistentCollectionGeneratorTest extends BaseTest
{
    /** @var DefaultPersistentCollectionGenerator */
    private $generator;

    public function setUp()
    {
        parent::setUp();
        $this->generator = new DefaultPersistentCollectionGenerator(
            $this->dm->getConfiguration()->getPersistentCollectionDir(),
            $this->dm->getConfiguration()->getPersistentCollectionNamespace()
        );
    }

    public function testNoReturnTypes()
    {
        $class = $this->generator->loadClass(CollNoReturnType::class, Configuration::AUTOGENERATE_EVAL);
        $coll = new $class(new CollNoReturnType(), $this->dm, $this->uow);
        $this->assertInstanceOf(CollNoReturnType::class, $coll);
    }

    public function testWithReturnType()
    {
        $class = $this->generator->loadClass(CollWithReturnType::class, Configuration::AUTOGENERATE_EVAL);
        $coll = new $class(new CollWithReturnType(), $this->dm, $this->uow);
        $this->assertInstanceOf(CollWithReturnType::class, $coll);
    }

    public function testWithNullableReturnType()
    {
        $class = $this->generator->loadClass(CollWithNullableReturnType::class, Configuration::AUTOGENERATE_EVAL);
        $coll = new $class(new CollWithNullableReturnType(), $this->dm, $this->uow);
        $this->assertInstanceOf(CollWithNullableReturnType::class, $coll);
    }
}
