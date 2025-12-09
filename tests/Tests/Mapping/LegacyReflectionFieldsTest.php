<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;
use Doctrine\ODM\MongoDB\Mapping\LegacyReflectionFields;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Address;
use Documents\User;
use LogicException;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

use function sprintf;

#[IgnoreDeprecations]
class LegacyReflectionFieldsTest extends BaseTestCase
{
    public function testGetSet(): void
    {
        $class = $this->dm->getClassMetadata(User::class);
        self::assertInstanceOf(LegacyReflectionFields::class, $class->reflFields);

        $user = new User();
        $user->setUsername('Jean');
        $user->setInheritedProperty('inherited');
        $address = new Address();
        $address->setCity('Paris');
        $user->setAddress($address);

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->getId());

        // Accessing the field directly through reflection
        self::assertEquals('Jean', $class->getReflectionProperty('username')->getValue($user));
        $class->getReflectionProperty('username')->setValue($user, 'Marie');
        self::assertEquals('Marie', $class->getReflectionProperty('username')->getValue($user));

        // Accessing a private field 'inheritedProperty' of the parent class through reflection
        self::assertEquals('inherited', $class->getReflectionProperty('inheritedProperty')->getValue($user));
        $class->getReflectionProperty('inheritedProperty')->setValue($user, 'changed');
        self::assertEquals('changed', $class->getReflectionProperty('inheritedProperty')->getValue($user));

        // Accessing a field in a related document through reflection
        self::assertEquals('Paris', $class->getReflectionProperty('address')->getValue($user)->getCity());
        $class->getReflectionProperty('address')->setValue($user, $newAddress = new Address());
        self::assertSame($newAddress, $class->getReflectionProperty('address')->getValue($user));

        // ArrayAccess and Countable interfaces
        self::assertCount(32, $class->reflFields);
        self::assertArrayHasKey('username', $class->reflFields);
        self::assertArrayNotHasKey('nonExistentField', $class->reflFields);
    }

    public function testGetSetReadonly(): void
    {
        $class = $this->dm->getClassMetadata(ReadOnlyProperty::class);
        self::assertInstanceOf(LegacyReflectionFields::class, $class->reflFields);

        $tag = new ReadOnlyProperty('Important');
        $this->dm->persist($tag);
        $this->dm->flush();

        $tag = $this->dm->find(ReadOnlyProperty::class, $tag->id);

        // Accessing the readonly property through reflection
        self::assertEquals('Important', $class->getReflectionProperty('name')->getValue($tag));

        self::expectException(LogicException::class);
        self::expectExceptionMessage(sprintf('Attempting to change readonly property %s::$name', ReadOnlyProperty::class));
        $class->getReflectionProperty('name')->setValue($tag, 'Very Important');
    }
}

#[Document]
class ReadOnlyProperty
{
    #[Id]
    public string $id;

    public function __construct(
        #[Field]
        public readonly string $name,
    ) {
    }
}
