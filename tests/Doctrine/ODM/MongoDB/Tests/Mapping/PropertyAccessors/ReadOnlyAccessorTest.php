<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping\PropertyAccessors;

use Doctrine\ODM\MongoDB\Mapping\PropertyAccessors\PropertyAccessorFactory;
use Doctrine\ODM\MongoDB\Mapping\PropertyAccessors\ReadonlyAccessor;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use LogicException;

class ReadOnlyAccessorTest extends BaseTestCase
{
    public function testReadOnlyProperty(): void
    {
        $object   = new ReadOnlyClass();
        $accessor = PropertyAccessorFactory::createPropertyAccessor(ReadOnlyClass::class, 'property');

        $this->assertInstanceOf(ReadonlyAccessor::class, $accessor);

        $accessor->setValue($object, 1);

        $this->assertEquals($object->property, 1);
        $this->assertEquals(1, $accessor->getValue($object));
    }

    public function testReadOnlyPropertyOnlyOnce(): void
    {
        $object   = new ReadOnlyClass();
        $accessor = PropertyAccessorFactory::createPropertyAccessor(ReadOnlyClass::class, 'property');

        $this->assertInstanceOf(ReadonlyAccessor::class, $accessor);

        $accessor->setValue($object, 1);
        $this->expectException(LogicException::class);
        $accessor->setValue($object, 2);
    }
}

class ReadOnlyClass
{
    // @phpstan-ignore property.uninitializedReadonly
    public readonly int $property;
}
