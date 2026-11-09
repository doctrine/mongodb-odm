<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping\PropertyAccessors;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\PropertyAccessors\DelegateCollectionPropertyAccessor;
use Doctrine\ODM\MongoDB\Mapping\PropertyAccessors\PropertyAccessorFactory;
use Doctrine\ODM\MongoDB\PersistentCollection\DelegateCollection;
use Doctrine\ODM\MongoDB\PersistentCollection\DelegateCollectionFactory;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use IteratorAggregate;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Traversable;

class DelegateCollectionPropertyAccessorTest extends TestCase
{
    public function testGetValueReturnsTheUnderlyingCollection(): void
    {
        $document = new DelegatedAccessorDocument();
        $accessor = $this->createAccessor();
        $inner    = $document->items->getDelegatee();

        self::assertSame($inner, $accessor->getValue($document));
    }

    public function testSetValueWritesIntoTheExistingInstance(): void
    {
        $document = new DelegatedAccessorDocument();
        $wrapper  = $document->items;
        $accessor = $this->createAccessor();
        $inner    = new ArrayCollection();

        $accessor->setValue($document, $inner);

        self::assertSame($wrapper, $document->items);
        self::assertSame($inner, $wrapper->getDelegatee());
    }

    public function testSetValueCreatesANewInstanceWithTheFactory(): void
    {
        $document = (new ReflectionClass(DelegatedAccessorDocument::class))->newInstanceWithoutConstructor();
        $factory  = new CountingDelegateCollectionFactory();
        $accessor = $this->createAccessor($factory);
        $inner    = new ArrayCollection();

        $accessor->setValue($document, $inner);

        self::assertSame(1, $factory->count);
        self::assertInstanceOf(DelegatedAccessorCollection::class, $document->items);
        self::assertSame($inner, $document->items->getDelegatee());
    }

    public function testSetValueCreatesANewInstanceWhenTheCollectionBelongsToAnotherDocument(): void
    {
        $document = new DelegatedAccessorDocument();
        $other    = new DelegatedAccessorDocument();
        $factory  = new CountingDelegateCollectionFactory();
        $accessor = $this->createAccessor($factory);
        $wrapper  = $document->items;

        $persistentCollection = $this->createMock(PersistentCollectionInterface::class);
        $persistentCollection->method('getOwner')->willReturn($other);
        $persistentCollection->method('getMapping')->willReturn(['fieldName' => 'items']);

        $wrapper->replaceCollection($persistentCollection);

        $accessor->setValue($document, new ArrayCollection());

        self::assertSame(1, $factory->count);
        self::assertNotSame($wrapper, $document->items);
        self::assertNotSame($persistentCollection, $document->items->getDelegatee());
    }

    public function testSetValueCreatesANewInstanceWhenTheCollectionBelongsToAnotherAssociation(): void
    {
        $document = new DelegatedAccessorDocument();
        $factory  = new CountingDelegateCollectionFactory();
        $accessor = $this->createAccessor($factory);

        $persistentCollection = $this->createMock(PersistentCollectionInterface::class);
        $persistentCollection->method('getOwner')->willReturn($document);
        $persistentCollection->method('getMapping')->willReturn(['fieldName' => 'other']);

        $document->items->replaceCollection($persistentCollection);

        $accessor->setValue($document, new ArrayCollection());

        self::assertSame(1, $factory->count);
    }

    public function testSetValueUnsetsTheProperty(): void
    {
        $document = new DelegatedAccessorDocument();
        $accessor = $this->createAccessor();

        $accessor->setValue($document, null);

        self::assertFalse(isset($document->items));
        self::assertNull($accessor->getValue($document));
    }

    public function testSetValueAcceptsADelegatedCollection(): void
    {
        $document = new DelegatedAccessorDocument();
        $accessor = $this->createAccessor();
        $wrapper  = new DelegatedAccessorCollection();

        $accessor->setValue($document, $wrapper);

        self::assertSame($wrapper, $document->items);
    }

    public function testResolveInnerPropertyRequiresACollectionProperty(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('exactly one property typed as Doctrine\Common\Collections\Collection');

        DelegateCollectionPropertyAccessor::resolveInnerProperty(DelegatedCollectionWithoutProperty::class);
    }

    public function testResolveInnerPropertyRequiresExactlyOneCollectionProperty(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('exactly one property typed as Doctrine\Common\Collections\Collection');

        DelegateCollectionPropertyAccessor::resolveInnerProperty(DelegatedCollectionWithTwoProperties::class);
    }

    public function testResolveInnerPropertyRejectsAReadonlyProperty(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must not be readonly');

        DelegateCollectionPropertyAccessor::resolveInnerProperty(DelegatedCollectionWithReadonlyProperty::class);
    }

    private function createAccessor(DelegateCollectionFactory|null $factory = null): DelegateCollectionPropertyAccessor
    {
        $parent = PropertyAccessorFactory::createPropertyAccessor(DelegatedAccessorDocument::class, 'items');

        return new DelegateCollectionPropertyAccessor(
            $parent,
            DelegatedAccessorCollection::class,
            'items',
            $factory,
        );
    }
}

class DelegatedAccessorDocument
{
    public DelegatedAccessorCollection $items;

    public function __construct()
    {
        $this->items = new DelegatedAccessorCollection();
    }
}

/**
 * @template-implements IteratorAggregate<array-key, object>
 * @template-implements DelegateCollection<array-key, object>
 */
final class DelegatedAccessorCollection implements DelegateCollection, IteratorAggregate
{
    /** @var Collection<array-key, object> */
    private Collection $collection;

    /** @param Collection<array-key, object>|null $collection */
    public function __construct(Collection|null $collection = null)
    {
        $this->collection = $collection ?? new ArrayCollection();
    }

    /** @return Collection<array-key, object> */
    public function getDelegatee(): Collection
    {
        return $this->collection;
    }

    /** @param Collection<array-key, object> $collection */
    public function replaceCollection(Collection $collection): void
    {
        $this->collection = $collection;
    }

    public function getIterator(): Traversable
    {
        return $this->collection->getIterator();
    }
}

/**
 * @template-implements IteratorAggregate<array-key, object>
 * @template-implements DelegateCollection<array-key, object>
 */
final class DelegatedCollectionWithoutProperty implements DelegateCollection, IteratorAggregate
{
    public function getIterator(): Traversable
    {
        yield from [];
    }
}

/**
 * @template-implements IteratorAggregate<array-key, object>
 * @template-implements DelegateCollection<array-key, object>
 */
final class DelegatedCollectionWithTwoProperties implements DelegateCollection, IteratorAggregate
{
    /** @var Collection<array-key, object> */
    private Collection $first;
    /** @var Collection<array-key, object> */
    private Collection $second;

    public function __construct()
    {
        $this->first  = new ArrayCollection();
        $this->second = new ArrayCollection();
    }

    public function getIterator(): Traversable
    {
        return $this->first->getIterator();
    }
}

/**
 * @template-implements IteratorAggregate<array-key, object>
 * @template-implements DelegateCollection<array-key, object>
 */
final class DelegatedCollectionWithReadonlyProperty implements DelegateCollection, IteratorAggregate
{
    /** @param Collection<array-key, object> $collection */
    public function __construct(private readonly Collection $collection)
    {
    }

    public function getIterator(): Traversable
    {
        return $this->collection->getIterator();
    }
}

final class CountingDelegateCollectionFactory implements DelegateCollectionFactory
{
    public int $count = 0;

    public function create(string $collectionClass): DelegateCollection
    {
        $this->count++;

        return new $collectionClass();
    }
}
