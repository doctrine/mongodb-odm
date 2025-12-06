<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;
use LogicException;
use ReflectionProperty;

/**
 * Test initialization of TypeRegistry in DocumentManager or globally.
 */
class DocumentManagerTypeRegistryTest extends BaseTestCase
{
    private ReflectionProperty $prop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prop = new ReflectionProperty(Type::class, 'registry');
        $this->prop->setValue(null, null);
    }

    public function testInitTypeRegistryWhenBothAreNull(): void
    {
        $dm = DocumentManager::create(null, $this->dm->getConfiguration());

        $this->assertSame($this->prop->getValue(), $dm->getTypes());
    }

    public function testInitTypeRegistryWhenOnlyGlobalIsSet(): void
    {
        $registry = new TypeRegistry();
        $this->prop->setValue(null, $registry);

        $dm = DocumentManager::create(null, $this->dm->getConfiguration());

        $this->assertSame($registry, $dm->getTypes());
        $this->assertSame($registry, $this->prop->getValue());
    }

    public function testInitTypeRegistryWhenOnlyInstanceIsSet(): void
    {
        $registry = new TypeRegistry();
        $dm       = DocumentManager::create(null, $this->dm->getConfiguration(), null, $registry);

        $this->assertSame($registry, $dm->getTypes());
        $this->assertSame($registry, $this->prop->getValue());
    }

    public function testInitTypeRegistryWhenBothAreTheSame(): void
    {
        $registry = new TypeRegistry();
        $this->prop->setValue(null, $registry);

        $dm = DocumentManager::create(null, $this->dm->getConfiguration(), null, $registry);

        $this->assertSame($registry, $dm->getTypes());
        $this->assertSame($registry, $this->prop->getValue());
    }

    public function testInitTypeRegistryThrowsExceptionWhenBothDiffer(): void
    {
        $globalRegistry   = new TypeRegistry();
        $instanceRegistry = new TypeRegistry();

        $this->prop->setValue(null, $globalRegistry);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Type registry is already set and cannot be modified. A single TypeRegistry instance is allowed, this will change in MongoDB ODM 3.0.');

        DocumentManager::create(null, $this->dm->getConfiguration(), null, $instanceRegistry);
    }

    public function testUsingMultipleDocumentManagersGetTheSameTypeRegistryByDefault(): void
    {
        $dm1 = DocumentManager::create(null, $this->dm->getConfiguration());
        $dm2 = DocumentManager::create(null, $this->dm->getConfiguration());

        $this->assertSame($dm1->getTypes(), $dm2->getTypes());
    }
}
