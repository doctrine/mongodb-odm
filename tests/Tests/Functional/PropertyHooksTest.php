<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\PropertyHooks\User;

class PropertyHooksTest extends BaseTestCase
{
    public function testMapPropertyHooks(): void
    {
        $user           = new User();
        $user->fullName = 'John Doe';
        $user->language = 'EN';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->id);

        self::assertSame('John', $user->first);
        self::assertSame('Doe', $user->last);
        self::assertSame('John Doe', $user->fullName);
        self::assertSame('EN', $user->language, 'The property hook uppercases the language.');

        $document = $this->dm->createQueryBuilder()
            ->find(User::class)
            ->field('id')->equals($user->id)
            ->select('language')
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();

        self::assertSame('en', $document['language'], 'Selecting a field without hydration does not go through the property hook, accessing raw data.');

        $this->dm->clear();

        $user = $this->dm->getRepository(User::class)->findOneBy(['language' => 'EN']);

        self::assertNull($user);

        $user = $this->dm->getRepository(User::class)->findOneBy(['language' => 'en']);

        self::assertNotNull($user);
    }

    public function testTriggerLazyLoadingWhenAccessingPropertyHooks(): void
    {
        $user           = new User();
        $user->fullName = 'Ludwig von Beethoven';
        $user->language = 'DE';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getReference(User::class, $user->id);

        $this->assertTrue($this->dm->getUnitOfWork()->isUninitializedObject($user));

        self::assertSame('Ludwig', $user->first);
        self::assertSame('von Beethoven', $user->last);
        self::assertSame('Ludwig von Beethoven', $user->fullName);
        self::assertSame('DE', $user->language, 'The property hook uppercases the language.');

        $this->assertFalse($this->dm->getUnitOfWork()->isUninitializedObject($user));

        $this->dm->clear();

        $user = $this->dm->getReference(User::class, $user->id);

        self::assertSame('Ludwig von Beethoven', $user->fullName);
    }

    public function testMappingVirtualPropertyIsNotSupported(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Mapping virtual property "fullName" on document "' . __NAMESPACE__ . '\MappingVirtualProperty" is not allowed.');

        $this->dm->getClassMetadata(MappingVirtualProperty::class);
    }
}

#[Document(collection: 'property_hooks_user')]
class MappingVirtualProperty
{
    // phpcs:disable
    #[Id]
    public ?string $id;

    #[Field]
    public string $first;

    #[Field]
    public string $last;

    #[Field]
    public string $fullName {
        get => $this->first . " " . $this->last;
        set {
            [$this->first, $this->last] = explode(' ', $value, 2);
        }
    }
    // phpcs:enable
}
