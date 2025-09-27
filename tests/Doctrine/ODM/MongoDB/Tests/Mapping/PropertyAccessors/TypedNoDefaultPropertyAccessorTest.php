<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping\PropertyAccessors;

use Doctrine\ODM\MongoDB\Mapping\PropertyAccessors\PropertyAccessorFactory;
use Doctrine\ODM\MongoDB\Mapping\PropertyAccessors\TypedNoDefaultPropertyAccessor;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class TypedNoDefaultPropertyAccessorTest extends BaseTestCase
{
    public function testSetValueWithoutDefault(): void
    {
        $accessor = PropertyAccessorFactory::createPropertyAccessor(TypedClass::class, 'property');

        $this->assertInstanceOf(TypedNoDefaultPropertyAccessor::class, $accessor);

        $object = new TypedClass();
        $accessor->setValue($object, 42);
        $this->assertEquals(42, $accessor->getValue($object));
    }

    public function testSetNullWithoutDefault(): void
    {
        $accessor = PropertyAccessorFactory::createPropertyAccessor(TypedClass::class, 'property');

        $object = new TypedClass();
        $accessor->setValue($object, null);
        $this->assertNull($accessor->getValue($object));

        $accessor->setValue($object, 42);
        $this->assertEquals(42, $accessor->getValue($object));

        $accessor->setValue($object, null);
        $this->assertNull($accessor->getValue($object));
    }
}

class TypedClass
{
    public int $property;
}
